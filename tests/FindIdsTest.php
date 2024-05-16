<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\Block;
use Torchlight\Torchlight;

class FindIdsTest extends BaseTestCase
{
    /** @test */
    public function it_will_find_all_the_ids()
    {
        $standard = Block::make();
        $custom1 = Block::make('custom-id');
        $custom2 = Block::make('custom-1234');

        $content = <<<EOT
{$standard->placeholder()}
{$standard->placeholder('styles')}

{$custom1->placeholder()}
{$custom1->placeholder('styles')}

<code style="{$custom2->placeholder('styles')}">{$custom2->placeholder()}</code>
EOT;

        $found = Torchlight::findTorchlightIds($content);

        $this->assertContains($standard->id(), $found);
        $this->assertContains('custom-id', $found);
        $this->assertContains('custom-1234', $found);
    }

    /** @test */
    public function it_only_returns_one_per()
    {
        $standard = Block::make();

        $content = <<<EOT
{$standard->placeholder()}
{$standard->placeholder()}
{$standard->placeholder()}
{$standard->placeholder()}
{$standard->placeholder()}
EOT;

        $found = Torchlight::findTorchlightIds($content);

        $this->assertContains($standard->id(), $found);
        $this->assertCount(1, $found);
    }

    /** @test */
    public function its_always_an_array()
    {
        $this->assertEquals([], Torchlight::findTorchlightIds('not found'));
    }
}
