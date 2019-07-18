<?php
declare(strict_types=1);
namespace TYPO3Fluid\Fluid\Tests\Unit\Core\ViewHelper;

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

use phpDocumentor\Reflection\DocBlock\Tag;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;
use TYPO3Fluid\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;
use TYPO3Fluid\Fluid\Tests\UnitTestCase;

/**
 * Testcase for TagBasedViewHelper
 */
class AbstractTagBasedViewHelperTest extends UnitTestCase
{
    /**
     * @var MockObject|ViewHelperInterface
     */
    protected $viewHelper;

    /**
     * @var MockObject|ViewHelperInterface
     */
    protected $tagBuilder;

    public function setUp(): void
    {
        $this->tagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['render', 'addAttribute', 'addAttributes'])->getMock();
        $this->viewHelper = $this->getMockBuilder(AbstractTagBasedViewHelper::class)->disableOriginalConstructor()->getMockForAbstractClass();
        $this->viewHelper->setRenderingContext(new RenderingContextFixture());
        $property = new \ReflectionProperty($this->viewHelper, 'tag');
        $property->setAccessible(true);
        $property->setValue($this->viewHelper, $this->tagBuilder);
    }

    /**
     * @test
     */
    public function testConstructorSetsTagBuilder(): void
    {
        $viewHelper = $this->getAccessibleMock(
            AbstractTagBasedViewHelper::class,
            ['dummy']
        );
        $this->assertAttributeInstanceOf(TagBuilder::class, 'tag', $viewHelper);
    }

    /**
     * @test
     */
    public function testRenderCallsRenderOnTagBuilder(): void
    {
        $this->tagBuilder->expects($this->once())->method('render')->willReturn('foobar');
        $this->assertEquals('foobar', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function oneTagAttributeIsRenderedCorrectly(): void
    {
        $method = new \ReflectionMethod($this->viewHelper, 'registerTagAttribute');
        $method->setAccessible(true);
        $method->invokeArgs($this->viewHelper, ['foo', 'string', 'Description']);
        $arguments = ['foo' => 'bar'];
        $this->viewHelper->setArguments($arguments);
        $this->tagBuilder->expects($this->once())->method('render')->willReturn('foobar');
        $output = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertSame('foobar', $output);
    }

    /**
     * @test
     */
    public function additionalTagAttributesAreRenderedCorrectly(): void
    {
        $this->tagBuilder->expects($this->once())->method('addAttributes')->with(['foo' => 'bar']);

        $arguments = ['additionalAttributes' => ['foo' => 'bar']];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function dataAttributesAreRenderedCorrectly(): void
    {
        $this->tagBuilder->expects($this->at(0))->method('addAttribute')->with('data-foo', 'bar');
        $this->tagBuilder->expects($this->at(1))->method('addAttribute')->with('data-baz', 'foos');

        $arguments = ['data' => ['foo' => 'bar', 'baz' => 'foos']];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->postParse($arguments, null, new ParsingState(), new RenderingContextFixture());
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function testValidateAdditionalArgumentsThrowsExceptionIfContainingNonDataArguments(): void
    {
        $this->setExpectedException(Exception::class);
        $this->viewHelper->validateAdditionalArguments(['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function testHandleAdditionalArgumentsSetsTagAttributesForDataArguments(): void
    {
        $this->tagBuilder->expects($this->at(0))->method('addAttribute')->with('data-foo', 'foo');
        $this->tagBuilder->expects($this->at(1))->method('addAttribute')->with('data-bar', 'bar');
        $this->viewHelper->handleAdditionalArguments(['data-foo' => 'foo', 'data-bar' => 'bar']);
    }

    /**
     * @test
     */
    public function standardTagAttributesAreRegistered(): void
    {
        $this->tagBuilder->expects($this->at(0))->method('addAttribute')->with('class', 'classAttribute');
        $this->tagBuilder->expects($this->at(1))->method('addAttribute')->with('dir', 'dirAttribute');
        $this->tagBuilder->expects($this->at(2))->method('addAttribute')->with('id', 'idAttribute');
        $this->tagBuilder->expects($this->at(3))->method('addAttribute')->with('lang', 'langAttribute');
        $this->tagBuilder->expects($this->at(4))->method('addAttribute')->with('style', 'styleAttribute');
        $this->tagBuilder->expects($this->at(5))->method('addAttribute')->with('title', 'titleAttribute');
        $this->tagBuilder->expects($this->at(6))->method('addAttribute')->with('accesskey', 'accesskeyAttribute');
        $this->tagBuilder->expects($this->at(7))->method('addAttribute')->with('tabindex', 'tabindexAttribute');

        $arguments = [
            'class' => 'classAttribute',
            'dir' => 'dirAttribute',
            'id' => 'idAttribute',
            'lang' => 'langAttribute',
            'style' => 'styleAttribute',
            'title' => 'titleAttribute',
            'accesskey' => 'accesskeyAttribute',
            'tabindex' => 'tabindexAttribute'
        ];

        $method = new \ReflectionMethod($this->viewHelper, 'registerUniversalTagAttributes');

        $method->setAccessible(true);
        $method->invoke($this->viewHelper);

        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initializeArgumentsAndRender();
    }
}
