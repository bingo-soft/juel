<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Juel\{
    Builder,
    Feature,
    ExpressionFactoryImpl,
    SimpleContext,
    SimpleResolver,
    TreeStore,
    TreeMethodExpression,
    TreeValueExpression
};
use El\ObjectELResolver;
use Util\Reflection\MetaObject;

class ExpressionLanguageTest extends TestCase
{
    public const TEST_CONST = 'test';
    public static $field = 'field';

    public static function someFunc(int $var): int
    {
        return $var * 5;
    }

    public function testNumericVariables(): void
    {
        $factory = new ExpressionFactoryImpl();
        $context = new SimpleContext();

        $context->setVariable("e", $factory->createValueExpression(null, null, M_E, "double"));
        $context->setVariable("pi", $factory->createValueExpression(null, null, M_PI, "double"));

        $vmapper = $context->getVariableMapper();

        $this->assertEquals(M_E, $vmapper->resolveVariable("e")->getValue($context));
        $this->assertEquals(M_PI, $vmapper->resolveVariable("pi")->getValue($context));

        $expr = $factory->createValueExpression($context, '${e < pi}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${e + 1}', null, "double");
        $this->assertEquals(M_E + 1, $expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${pi}', null, "double");
        $this->assertEquals(M_PI, $expr->getValue($context));

        $context->setVariable("a", $factory->createValueExpression(null, null, 1, "integer"));
        $context->setVariable("b", $factory->createValueExpression(null, null, 2, "integer"));
        $expr = $factory->createValueExpression($context, '${a + b}', null, "integer");
        $this->assertEquals(3, $expr->getValue($context));

        $context->setVariable("c", $factory->createValueExpression(null, null, 3, "integer"));
        $expr = $factory->createValueExpression($context, '${a + b * c}', null, "integer");
        $this->assertEquals(7, $expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${(a + b) * c}', null, "integer");
        $this->assertEquals(9, $expr->getValue($context));
    }

    public function testNumericMethods(): void
    {
        $factory = new ExpressionFactoryImpl();
        $context = new SimpleContext();

        $wrapper =  new \ReflectionClass(SimpleClass::class);

        $context->setVariable("e", $factory->createValueExpression(null, null, M_E, "double"));
        $context->setVariable("pi", $factory->createValueExpression(null, null, M_PI, "double"));
        $context->setVariable("arr", $factory->createValueExpression(null, null, [1, 2, 3], "array"));

        $context->setFunction("", "sin", $wrapper->getMethod("sin"));
        $context->setFunction("", "cos", $wrapper->getMethod("cos"));
        $context->setFunction("", "in_array", $wrapper->getMethod("inArray"));

        $fmapper = $context->getFunctionMapper();

        $this->assertEqualsWithDelta(1, $fmapper->resolveFunction("", "sin")->invoke(null, M_PI / 2), 7e-17);
        $this->assertEqualsWithDelta(0, $fmapper->resolveFunction("", "cos")->invoke(null, M_PI / 2), 7e-17);

        $expr = $factory->createValueExpression($context, '${sin(pi / 2) + cos(pi / 4) * sin(pi / 3)}', null, "double");
        $this->assertEquals(sin(M_PI / 2) + cos(M_PI / 4) * sin(M_PI / 3), $expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${in_array(2, arr)}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${in_array(5, arr)}', null, "boolean");
        $this->assertFalse($expr->getValue($context));
    }

    public function testBooleanMethods(): void
    {
        $factory = new ExpressionFactoryImpl();
        $context = new SimpleContext();

        $expr = $factory->createValueExpression($context, '${true}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${false}', null, "boolean");
        $this->assertFalse($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${true or false}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '${true and false}', null, "boolean");
        $this->assertFalse($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '#{true == true}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '#{1 >= 1}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '#{1 <= 1}', null, "boolean");
        $this->assertTrue($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '#{1 > 1}', null, "boolean");
        $this->assertFalse($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '#{1 < 1}', null, "boolean");
        $this->assertFalse($expr->getValue($context));

        $expr = $factory->createValueExpression($context, '#{null == null}', null, "boolean");
        $this->assertTrue($expr->getValue($context));
    }

    public function testMethodInvocation(): void
    {
        $context = new SimpleContext(new SimpleResolver(new ObjectELResolver()));
        $store = new TreeStore(new Builder([Feature::METHOD_INVOCATIONS]), null);

        $simple = new SimpleClass();
        $factory = new ExpressionFactoryImpl();

        $context->getELResolver()->setValue($context, null, "base", $simple);
        $expr = new TreeMethodExpression($store, null, null, null, '${base.foo}', null);
        $this->assertEquals(1, $expr->invoke($context));

        $ser = serialize($expr);
        $des = unserialize($ser);
        $this->assertEquals(1, $des->invoke($context));
    }

    public function testExpressionString(): void
    {
        $store = new TreeStore(new Builder(), null);
        $this->assertEquals("foo", (new TreeValueExpression($store, null, null, null, "foo", "object"))->getExpressionString());
    }

    public function testIsDeferred(): void
    {
        $store = new TreeStore(new Builder(), null);
        $this->assertFalse((new TreeValueExpression($store, null, null, null, "foo", "object"))->isDeferred());
        $this->assertFalse((new TreeValueExpression($store, null, null, null, '${foo}', "object"))->isDeferred());
        $this->assertTrue((new TreeValueExpression($store, null, null, null, "#{foo}", "object"))->isDeferred());
    }

    public function testGetExpectedType(): void
    {
        $store = new TreeStore(new Builder(), null);
        $this->assertEquals("object", (new TreeValueExpression($store, null, null, null, '${foo}', "object"))->getExpectedType());
        $this->assertEquals("string", (new TreeValueExpression($store, null, null, null, '${foo}', "string"))->getExpectedType());
    }

    public function testGetType(): void
    {
        $store = new TreeStore(new Builder(), null);
        $context = new SimpleContext();
        $this->assertFalse((new TreeValueExpression($store, null, null, null, '${property_foo}', "object"))->isReadOnly($context));
    }

    public function testOgnlSyntax(): void
    {
        $context = new SimpleContext();
        $simple = new SimpleClass();
        $factory = new ExpressionFactoryImpl();
        $context->getELResolver()->setValue($context, null, "base", $simple);
        $context->getELResolver()->setValue($context, null, "var", 3.2);
        $context->getELResolver()->setValue($context, null, "_parameter", 1);

        $expr = $factory->createValueExpression($context, '${2.1 + var + _parameter + propFloat + privateFloat + cos(0) + foo() + goo()}', null, "double");   
        $this->assertEqualsWithDelta(21.17, $expr->getValue($context), 1e-14);
    }

    public function testMetaObjectWithOgnlSyntax(): void
    {
        $context = new SimpleContext();
        $wrapper = new SimpleClass();
        $simple = new MetaObject($wrapper);
        $factory = new ExpressionFactoryImpl();
        $context->getELResolver()->setValue($context, null, "base", $simple);
        $context->getELResolver()->setValue($context, null, "var", 3.2);

        $expr = $factory->createValueExpression($context, '${2.1 + var + propFloat + privateFloat + cos(0) + foo() + goo()}', null, "double");   
        $this->assertEqualsWithDelta(20.17, $expr->getValue($context), 1e-14);
    }

    public function testAlphaNumericPropertyAndMethod(): void
    {
        $context = new SimpleContext();
        $simple = new SimpleClass();
        $factory = new ExpressionFactoryImpl();
        $context->getELResolver()->setValue($context, null, "base", $simple);
        $expr = $factory->createValueExpression($context, '${alpha1 + beta2()}', null, "integer");   
        $this->assertEquals(107, $expr->getValue($context));
    }

    public function testNestedPropertyInMetaObject(): void
    {
        $context = new SimpleContext();

        $rich1 = new RichType();
        $meta1 = new MetaObject($rich1);
        $meta1->setValue("richType.richType.richField", 10);

        $rich2 = new RichType();
        $meta2 = new MetaObject($rich2);
        $meta2->setValue("richType.richField", 23);

        $simple = new SimpleClass();
        $factory = new ExpressionFactoryImpl();
        $context->getELResolver()->setValue($context, null, "base", $simple);

        $factory = new ExpressionFactoryImpl();        
        
        $context->getELResolver()->setValue($context, null, "first", $meta1);
        $context->getELResolver()->setValue($context, null, "second", $meta2);
        $context->getELResolver()->setValue($context, null, "simple", $simple);
        
        $expr = $factory->createValueExpression($context, '${richType.richType.richField + 11 + beta2() + richType.richField + alpha1}', null, "integer");
        $this->assertEquals(151, $expr->getValue($context));
    }

    public function testNullObjectPropertyExpression(): void
    {
        $context = new SimpleContext();
        $simple = new Bean(null);
        $factory = new ExpressionFactoryImpl();
        $context->getELResolver()->setValue($context, null, "base", $simple);
        $expr = $factory->createValueExpression($context, '${id}', null, "object");
        $this->assertNull($expr->getValue($context));
    }

    public function testStaticArrayExpression(): void
    {
        $context = new SimpleContext();
        $factory = new ExpressionFactoryImpl();
        $expr = $factory->createValueExpression($context, ' ${ [1 , "2"] } ', null, "array");
        $this->assertEquals([1, "2"], $expr->getValue($context));
    }

    public function testClassConstantExpression(): void
    {
        $context = new SimpleContext();
        $factory = new ExpressionFactoryImpl();
        $expr = $factory->createValueExpression($context, ' ${ [ @\Tests\ExpressionLanguageTest::TEST_CONST, @\Tests\ExpressionLanguageTest::$field ] } ', null, "array");
        $this->assertEquals(["test", "field"], $expr->getValue($context));
    }

    public function testClassStaticMethodCallInExpression(): void
    {
        $context = new SimpleContext();
        $factory = new ExpressionFactoryImpl();
        $context->setVariable("var1", $factory->createValueExpression(null, null, 2, "integer"));
        $expr = $factory->createValueExpression($context, '${@\Tests\ExpressionLanguageTest::someFunc(var1)}', null, "integer");
        $this->assertEquals(10, $expr->getValue($context));
    }

    public function testInArrayExpression(): void
    {
        $context = new SimpleContext();
        $factory = new ExpressionFactoryImpl();
        $expr = $factory->createValueExpression($context, ' ${ !in_array(@\Tests\ExpressionLanguageTest::TEST_CONST, ["test" , null]) } ', null, "boolean");
        $this->assertFalse($expr->getValue($context));
    }

    //@TODO implement "$value IN $list" && "$value NOT IN $list" && "NOT $value IN $list"

    public function testEnumType(): void
    {
        $context = new SimpleContext();
        $rich1 = new RichType();
        $rich1->setEnumType(Type::EMPLOYEE);
        $meta1 = new MetaObject($rich1);
        $meta1->setValue("type", Type::DIRECTOR);

        $context->getELResolver()->setValue($context, null, "first", $meta1);
        $factory = new ExpressionFactoryImpl();     
        $expr = $factory->createValueExpression($context, '${type.value}', null, "string");
        $this->assertEquals(Type::DIRECTOR->value, $expr->getValue($context));
    }
}
