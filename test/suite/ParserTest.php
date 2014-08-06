<?php

namespace Minime\Annotations;

use \ReflectionProperty;
use Minime\Annotations\Fixtures\AnnotationsFixture;
use Minime\Annotations\Interfaces\ParserInterface;

/**
 * @group parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    private $fixture;

    private $parser;

    public function setUp()
    {
        $this->fixture = new AnnotationsFixture;
        $this->parser = new Parser;
    }

    private function getDocblock($fixture)
    {
        $reflection = new ReflectionProperty($this->fixture, $fixture);

        return $reflection->getDocComment();
    }

    /**
     * @test
     */
    public function parseEmptyFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('empty_fixture'));
        $this->assertSame([], $annotations);
    }

    /**
     * @test
     */
    public function parseNullFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('null_fixture'));
        $this->assertSame([null, ''], $annotations['value']);
    }

    /**
     * @test
     */
    public function parseBooleanFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('boolean_fixture'));
        $this->assertSame([true, false, "true", "false"], $annotations['value']);
    }

    /**
     * @test
     */
    public function parseImplicitBooleanFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('implicit_boolean_fixture'));
        $this->assertSame(true, $annotations['alpha']);
        $this->assertSame(true, $annotations['beta']);
        $this->assertSame(true, $annotations['gamma']);
        $this->assertArrayNotHasKey('delta', $annotations);
    }

    /**
     * @test
     */
    public function parseStringFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('string_fixture'));
        $this->assertSame(['abc', 'abc', 'abc ', '123'], $annotations['value']);
        $this->assertSame(['abc', 'abc', 'abc ', '123'], $annotations['value']);
    }

    /**
     * @test
     */
    public function parseIdentifierFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('identifier_parsing_fixture'));
        $this->assertSame(['bar' => 'test@example.com', 'toto' => true, 'tata' => true, 'number' => 2.1], $annotations);
    }

    /**
     * @test
     */
    public function parseIntegerFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('integer_fixture'));
        $this->assertSame([123, 23, -23], $annotations['value']);
    }

    /**
     * @test
     */
    public function parseFloatFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('float_fixture'));
        $this->assertSame([.45, 0.45, 45., -4.5], $annotations['value']);
    }

    /**
     * @test
     */
    public function parseJsonFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('json_fixture'));
        $this->assertEquals(
            [
                ["x", "y"],
                json_decode('{"x": {"y": "z"}}'),
                json_decode('{"x": {"y": ["z", "p"]}}')
            ],
            $annotations['value']
        );
    }

    /**
     * @test
     */
    public function parseConcreteFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('concrete_fixture'));
        $this->assertInstanceOf(
          'Minime\Annotations\Fixtures\AnnotationConstructInjection',
          $annotations['Minime\Annotations\Fixtures\AnnotationConstructInjection'][0]
        );
        $this->assertInstanceOf(
          'Minime\Annotations\Fixtures\AnnotationConstructInjection',
          $annotations['Minime\Annotations\Fixtures\AnnotationConstructInjection'][1]
        );
        $this->assertSame(
          '{"foo":"bar","bar":"baz"}',
          json_encode($annotations['Minime\Annotations\Fixtures\AnnotationConstructInjection'][0])
        );
        $this->assertSame(
          '{"foo":"bar","bar":"baz"}',
          json_encode($annotations['Minime\Annotations\Fixtures\AnnotationConstructInjection'][1])
        );
        $this->assertInstanceOf(
          'Minime\Annotations\Fixtures\AnnotationSetterInjection',
          $annotations['Minime\Annotations\Fixtures\AnnotationSetterInjection'][0]
        );
        $this->assertInstanceOf(
          'Minime\Annotations\Fixtures\AnnotationSetterInjection',
          $annotations['Minime\Annotations\Fixtures\AnnotationSetterInjection'][1]
        );
        $this->assertSame(
          '{"foo":"bar"}',
          json_encode($annotations['Minime\Annotations\Fixtures\AnnotationSetterInjection'][0])
        );
        $this->assertSame(
          '{"foo":"bar"}',
          json_encode($annotations['Minime\Annotations\Fixtures\AnnotationSetterInjection'][1])
        );
    }

    /**
     * @test
     * @expectedException \Minime\Annotations\ParserException
     * @dataProvider invalidConcreteAnnotationFixtureProvider
     */
    public function parseInvalidConcreteFixture($fixture)
    {
        $this->parser->parse($this->getDocblock($fixture));
    }

    public function invalidConcreteAnnotationFixtureProvider()
    {
      return [
        ['bad_concrete_fixture'],
        ['bad_concrete_fixture_root_schema'],
        ['bad_concrete_fixture_method_schema']
      ];
    }

    /**
     * @test
     */
    public function parseSingleValuesFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('single_values_fixture'));
        $this->assertEquals('foo', $annotations['param_a']);
        $this->assertEquals('bar', $annotations['param_b']);
    }

    /**
     * @test
     */
    public function parseMultipleValuesFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('multiple_values_fixture'));
        $this->assertEquals(['x', 'y', 'z'], $annotations['value']);
    }

    /**
     * @test
     */
    public function parseParseSameLineFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('same_line_fixture'));
        $this->assertSame(true, $annotations['get']);
        $this->assertSame(true, $annotations['post']);
        $this->assertSame(true, $annotations['ajax']);
        $this->assertSame(true, $annotations['alpha']);
        $this->assertSame(true, $annotations['beta']);
        $this->assertSame(true, $annotations['gamma']);
        $this->assertArrayNotHasKey('undefined', $annotations);
    }

    /**
     * @test
     */
    public function parseMultilineValueFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('multiline_value_fixture'));
        $string = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.\n"
                  ."Etiam malesuada mauris justo, at sodales nisi accumsan sit amet.\n\n"
                  ."Morbi imperdiet lacus non purus suscipit convallis.\n"
                  ."Suspendisse egestas orci a felis imperdiet, non consectetur est suscipit.";
        $this->assertSame($string, $annotations['multiline_string']);

        $cowsay = "------\n< moo >\n------ \n        \   ^__^\n         ".
                  "\  (oo)\_______\n            (__)\       )\/\\\n                ".
                  "||----w |\n                ||     ||";
        $this->assertSame($cowsay, $annotations['multiline_indented_string']);

        $this->assertEquals(json_decode('{"x": {"y": ["z", "p"]}}'), $annotations['multiline_json']);
    }

    /**
     * @test
     */
    public function parseNamespacedAnnotations()
    {
        $annotations = $this->parser->parse($this->getDocblock('namespaced_fixture'));

        $this->assertSame('cheers!', $annotations['path.to.the.treasure']);
        $this->assertSame('the cake is a lie', $annotations['path.to.the.cake']);
        $this->assertSame('foo', $annotations['another.path.to.cake']);
    }

    /**
     * @test
     */
    public function parseStrongTypedFixture()
    {
        $annotations = $this->parser->parse($this->getDocblock('strong_typed_fixture'));
        $declarations = $annotations['value'];
        $this->assertNotEmpty($declarations);
        $this->assertSame(
            [
            "abc", "45", // string
            45, -45, // integer
            .45, 0.45, 45.0, -4.5, 4., // float
            ],
            $declarations
        );

        $declarations = $annotations['json_value'];
        $this->assertEquals(
            [
            ["x", "y"], // json
            json_decode('{"x": {"y": "z"}}'),
            json_decode('{"x": {"y": ["z", "p"]}}')
            ],
            $declarations
        );
    }

    /**
     * @test
     */
    public function parseReservedWordsAsValue()
    {
        $annotations = $this->parser->parse($this->getDocblock('reserved_words_as_value_fixture'));
        $expected = ['string','integer','float','json'];
        $this->assertSame($expected, $annotations['value']);
        $this->assertSame($expected, $annotations['value_with_trailing_space']);
    }

    /**
     * @test
     */
    public function tolerateUnrecognizedTypes()
    {
        $annotations = $this->parser->parse($this->getDocblock('non_recognized_type_fixture'));
        $this->assertEquals(
          "footype Tolerate me. DockBlocks can't be evaluated rigidly.", $annotations['value']);
    }

    /**
     * @test
     */
    public function parseInlineDocblocks()
    {
        $annotations = $this->parser->parse($this->getDocblock('inline_docblock_fixture'));
        $this->assertSame('foo', $annotations['value']);

        $annotations = $this->parser->parse($this->getDocblock('inline_docblock_implicit_boolean_fixture'));
        $this->assertSame(true, $annotations['alpha']);

        $annotations = $this->parser->parse($this->getDocblock('inline_docblock_multiple_implicit_boolean_fixture'));
        $this->assertSame(true, $annotations['alpha']);
        $this->assertSame(true, $annotations['beta']);
        $this->assertSame(true, $annotations['gama']);
    }

    /**
     * @test
     * @expectedException \Minime\Annotations\ParserException
     */
    public function exceptionWithBadJsonValue()
    {
        $this->parser->parse($this->getDocblock('bad_json_fixture'));
    }

    /**
     * @test
     * @expectedException \Minime\Annotations\ParserException
     */
    public function exceptionWithBadIntegerValue()
    {
        $this->parser->parse($this->getDocblock('bad_integer_fixture'));
    }

    /**
     * @test
     * @expectedException \Minime\Annotations\ParserException
     */
    public function exceptionWithBadFloatValue()
    {
        $this->parser->parse($this->getDocblock('bad_float_fixture'));
    }

    /**
     * @test for issue #32
     * @link https://github.com/marcioAlmada/annotations/issues/32
     */
    public function issue32()
    {
      $annotations = $this->parser->parse($this->getDocblock('i32_fixture'));
      $this->assertSame(['stringed', 'integers', 'floated', 'jsonable'], $annotations['type']);
    }

    /**
     * @test for issue #49
     * @link https://github.com/marcioAlmada/annotations/issues/49
     */
    public function issue49()
    {
      $annotations = $this->parser->parse($this->getDocblock('i49_fixture'));
      $this->assertSame(['return' => 'void'], $annotations);
    }
}
