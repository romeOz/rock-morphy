<?php

namespace rockunit;


use rock\morphy\PHPMorphy;

class PHPMorphyTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PHPMorphy */
    protected $morphy;

    protected function setUp()
    {
        parent::setUp();
        $this->morphy = new PHPMorphy(['locale' => 'ru']);
    }

    public function testInflectionalForms()
    {
        $expected = [
            'ПРИВЕТ' =>
                [
                    0 => 'ПРИВЕТ',
                    1 => 'ПРИВЕТА',
                    2 => 'ПРИВЕТУ',
                    3 => 'ПРИВЕТОМ',
                    4 => 'ПРИВЕТЕ',
                    5 => 'ПРИВЕТЫ',
                    6 => 'ПРИВЕТОВ',
                    7 => 'ПРИВЕТАМ',
                    8 => 'ПРИВЕТАМИ',
                    9 => 'ПРИВЕТАХ',
                ],
            'МИР' =>
                [
                    0 => 'МИР',
                    1 => 'МИРА',
                    2 => 'МИРУ',
                    3 => 'МИРОМ',
                    4 => 'МИРЕ',
                    5 => 'МИРЫ',
                    6 => 'МИРОВ',
                    7 => 'МИРАМ',
                    8 => 'МИРАМИ',
                    9 => 'МИРАХ',
                    10 => 'МИРО',
                ],
        ];
        $this->assertEquals($expected, $this->morphy->inflectionalForms('привет мир'));
    }

    public function testBaseForm()
    {
        $this->assertEquals('ПРИВЕТ МИР МИРО БЫТЬ', $this->morphy->baseForm('привет, мир! и он был'));
    }

    public function testHighlight()
    {
        $expected = 'привет, <span class="highlight">мир</span>! и он был голубь <span class="highlight">мира</span>';
        $this->assertSame($expected, $this->morphy->highlight('мир','привет, мир! и он был голубь мира'));
    }
}
