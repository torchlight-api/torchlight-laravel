<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\Block;
use Torchlight\Torchlight;

class BlockTest extends BaseTestCase
{
    /** @test */
    public function it_dedents_code()
    {
        $block = Block::make();

        $code = <<<EOT
    echo 1;
    if (1) {
        return;
    }
EOT;

        $block->code($code);

        $dedented = <<<EOT
echo 1;
if (1) {
    return;
}
EOT;

        $this->assertEquals($block->code, $dedented);
    }

    /** @test */
    public function it_replaces_tabs()
    {
        $block = Block::make();

        $block->code("if (1) {\n\tif (1) {\n\t\treturn;\n\t}\n}");

        $cleaned = <<<EOT
if (1) {
    if (1) {
        return;
    }
}
EOT;

        $this->assertEquals($block->code, $cleaned);
    }

    /** @test */
    public function can_change_tab_size()
    {
        Torchlight::getConfigUsing([
            'tab_width' => 2
        ]);

        $block = Block::make();

        $block->code("if (1) {\n\tif (1) {\n\t\treturn;\n\t}\n}");

        $cleaned = <<<EOT
if (1) {
  if (1) {
    return;
  }
}
EOT;

        $this->assertEquals($block->code, $cleaned);
    }

    /** @test */
    public function can_leave_tabs_in()
    {
        Torchlight::getConfigUsing([
            'tab_width' => false
        ]);

        $block = Block::make();

        $block->code("if (1) {\n\tif (1) {\n\t\treturn;\n\t}\n}");

        $cleaned = "if (1) {\n\tif (1) {\n\t\treturn;\n\t}\n}";

        $this->assertEquals($block->code, $cleaned);
    }

    /** @test */
    public function it_right_trims()
    {
        $block = Block::make()->code('echo 1;      ');

        $this->assertEquals($block->code, 'echo 1;');
    }

    /** @test */
    public function you_can_set_your_own_id()
    {
        $block = Block::make('custom_id');

        $this->assertEquals($block->id(), 'custom_id');
    }

    /** @test */
    public function it_will_set_an_id()
    {
        $block = Block::make();

        $this->assertNotNull($block->id());
    }

    /** @test */
    public function hash_is_calculated()
    {
        $block = Block::make();

        $this->assertNotNull($hash = $block->hash());

        $block->code('new code');

        $this->assertNotEquals($hash, $hash = $block->hash());

        $block->theme('new theme');

        $this->assertNotEquals($hash, $hash = $block->hash());

        $block->language('new language');

        $this->assertNotEquals($hash, $hash = $block->hash());

        config()->set('torchlight.bust', 'new bust');

        $this->assertNotEquals($hash, $hash = $block->hash());

        // Hashes are stable if nothing changes.
        $this->assertEquals($hash, $block->hash());
    }

    /** @test */
    public function to_request_params_includes_required_info()
    {
        $block = Block::make('id');
        $block->code('new code');
        $block->theme('new theme');
        $block->language('new language');

        $this->assertEquals([
            'id' => 'id',
            'hash' => 'e3db0a2768764be87d79e90063d21009',
            'language' => 'new language',
            'theme' => 'new theme',
            'code' => 'new code',
        ], $block->toRequestParams());
    }

    /** @test */
    public function default_theme_is_used()
    {
        config()->set('torchlight.theme', 'a new default');

        $block = Block::make('id');

        $this->assertEquals('a new default', $block->theme);
    }

    /** @test */
    public function can_specify_an_id_generator()
    {
        Block::$generateIdsUsing = function () {
            return 'generated_via_test';
        };

        $block = Block::make();

        $this->assertEquals('generated_via_test', $block->id());

        Block::$generateIdsUsing = null;
    }
}
