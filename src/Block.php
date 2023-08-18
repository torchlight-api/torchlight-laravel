<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Block
{
    use Macroable;

    /**
     * @var null|callable
     */
    public static $generateIdsUsing;

    /**
     * The language of the code that is being highlighted.
     *
     * @var string
     */
    public $language;

    /**
     * The theme of the code.
     *
     * @var string
     */
    public $theme;

    /**
     * The code itself.
     *
     * @var string
     */
    public $code;

    /**
     * The post processors.
     *
     * @var array
     */
    public $postProcessors = [];

    /**
     * The highlighted code, wrapped in pre+code tags.
     *
     * @var string
     */
    public $wrapped;

    /**
     * The highlighted code, not wrapped.
     *
     * @var string
     */
    public $highlighted;

    /**
     * Classes that should be applied to the code tag.
     *
     * @var string
     */
    public $classes;

    /**
     * Styles that should be applied to the code tag.
     *
     * @var string
     */
    public $styles;

    /**
     * Attributes to apply to the code tag.
     *
     * @var array
     */
    public $attrs = [];

    /**
     * The unique ID for the block.
     *
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $clones = [];

    /**
     * @param  null|string  $id
     * @return static
     */
    public static function make($id = null)
    {
        return new static($id);
    }

    /**
     * @param  null|string  $id
     */
    public function __construct($id = null)
    {
        // Generate a unique UUID.
        $this->id = $id ?? $this->generateId();

        // Set a default theme.
        $this->theme(Torchlight::config('theme'));
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    protected function generateId()
    {
        $id = is_callable(static::$generateIdsUsing) ? call_user_func(static::$generateIdsUsing) : Str::uuid();

        return (string)$id;
    }

    /**
     * @return string
     */
    public function hash()
    {
        return md5(
            $this->language
            . $this->theme
            . $this->code
            . Torchlight::config('bust')
            . json_encode(Torchlight::config('options'))
        );
    }

    /**
     * @return array
     */
    public function clones()
    {
        return $this->clones;
    }

    /**
     * @param  $num
     * @return $this
     */
    public function cloned($num)
    {
        $this->id = Str::finish($this->id, "_clone_$num");

        return $this;
    }

    /**
     * @param  string  $extra
     * @return string
     */
    public function placeholder($extra = '')
    {
        if ($extra) {
            $extra = "_$extra";
        }

        return "__torchlight-block-[{$this->id()}]{$extra}__";
    }

    /**
     * @param  $language
     * @return $this
     */
    public function language($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param  $theme
     * @return $this
     */
    public function theme($theme)
    {
        $theme = $this->normalizeArrayTheme($theme);

        if ($theme) {
            $this->theme = $theme;
        }

        return $this;
    }

    /**
     * @param  $code
     * @return $this
     */
    public function code($code)
    {
        $this->code = $this->clean($code);

        return $this;
    }

    /**
     * @return string
     */
    public function attrsAsString()
    {
        $attrs = [];

        foreach ($this->attrs as $key => $value) {
            $value = addslashes($value ?? '');
            $attrs[] = "$key=\"$value\"";
        }

        if (count($attrs)) {
            return implode(' ', $attrs) . ' ';
        }
    }

    /**
     * @param  $processor
     * @return $this
     */
    public function addPostProcessor($processor)
    {
        if ($processor) {
            $this->postProcessors[] = Torchlight::validatedPostProcessor($processor);
        }

        return $this;
    }

    /**
     * @param  $wrapped
     * @return $this
     */
    public function wrapped($wrapped)
    {
        $this->wrapped = $wrapped;

        return $this;
    }

    /**
     * @return Block[]
     */
    public function spawnClones()
    {
        $this->clones = [];

        $themes = explode(',', $this->theme ?? '');

        // Set the theme for the current block, so that we
        // don't break the reference to it.
        $this->theme(array_shift($themes));

        // Then generate any clones for the remaining themes.
        $this->clones = collect($themes)->map(function ($theme, $num) {
            return (clone $this)->theme($theme)->cloned($num);
        })->toArray();

        return $this->clones;
    }

    /**
     * @return array
     */
    public function toRequestParams()
    {
        return [
            'id' => $this->id(),
            'hash' => $this->hash(),
            'language' => $this->language,
            'theme' => $this->theme,
            'code' => $this->code,
        ];
    }

    /**
     * @param  $theme
     * @return mixed|string
     */
    protected function normalizeArrayTheme($theme)
    {
        if (!is_array($theme)) {
            return $theme;
        }

        if (Arr::isAssoc($theme)) {
            return collect($theme)->map(function ($name, $label) {
                return "$label:$name";
            })->join(',');
        }

        return implode(',', $theme);
    }

    /**
     * @param  $code
     * @return string
     */
    protected function clean($code)
    {
        $code = rtrim($code);
        $code = $this->replaceTabs($code);

        return $this->dedent($code);
    }

    /**
     * @param  $code
     * @return string
     */
    protected function replaceTabs($code)
    {
        $multiplier = Torchlight::config('tab_width', 4);

        if ($multiplier === false) {
            return $code;
        }

        return str_replace("\t", str_repeat(' ', $multiplier), $code);
    }

    /**
     * @param  $code
     * @return string
     */
    protected function dedent($code)
    {
        $lines = explode("\n", $code);

        $dedent = collect($lines)
            ->map(function ($line) {
                if (!$line || $line === "\n") {
                    return false;
                }

                // Figure out how many spaces are at the start of the line.
                return strlen($line) - strlen(ltrim($line, ' '));
            })
            ->reject(function ($count) {
                return $count === false;
            })
            // Take the smallest number of left-spaces. We'll
            // dedent everything by that amount.
            ->min();

        // Make the string out of the right number of spaces.
        $dedent = str_repeat(' ', $dedent);

        return collect($lines)
            ->map(function ($line) use ($dedent) {
                $line = rtrim($line);

                // Replace the first n-many spaces that
                // are common to every line.
                return Str::replaceFirst($dedent, '', $line);
            })
            ->implode("\n");
    }
}
