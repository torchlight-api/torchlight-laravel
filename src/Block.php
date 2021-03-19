<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Torchlight;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Block
{
    public $language;

    public $theme;

    public $code;

    public $html;

    protected $id;

    /**
     * @param null|string $id
     * @return static
     */
    public static function make($id = null)
    {
        return new static($id);
    }

    /**
     * @param null|string $id
     */
    public function __construct($id = null)
    {
        // Generate a unique UUID.
        $this->id = $id ?? (string)Str::uuid();
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
    public function hash()
    {
        return md5(
            $this->language .
            $this->theme .
            $this->code .
            config('torchlight.bust', 1)
        );
    }

    /**
     * @return string
     */
    public function placeholder()
    {
        return "__torchlight-block-{$this->id()}__";
    }

    /**
     * @param $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $this->clean($code);

        return $this;
    }

    /**
     * @param $html
     * @return $this
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
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
            'theme' => $this->theme ?? config('torchlight.theme'),
            'code' => $this->code,
        ];
    }

    /**
     * @param $code
     * @return string
     */
    protected function clean($code)
    {
        return $this->dedent(
            Str::replaceLast("\n", '', $code)
        );
    }

    /**
     * @param $code
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
