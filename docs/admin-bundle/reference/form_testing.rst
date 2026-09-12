.. index::
    double: Test Widgets; Definition

Testing
=======

Test Widgets
~~~~~~~~~~~~

``IDCT\Adminata\Test\AbstractWidgetTestCase`` renders a form view through Twig with the admin
bundle's form themes loaded, so a widget test asserts on the markup a page would really get. You
can write unit tests for Twig form rendering with the following code::

    use IDCT\Adminata\Test\AbstractWidgetTestCase;

    class CustomTest extends AbstractWidgetTestCase
    {
        public function testAcmeWidget(): void
        {
            $options = [
                'foo' => 'bar',
            ];

            $form     = $this->factory->create('Acme\Form\CustomType', null, $options);
            $html     = $this->renderWidget($form->createView());
            $expected = '<input foo="bar"/>';

            $this->assertStringContainsString($expected, $this->cleanHtmlWhitespace($html));
        }
    }

``renderWidget()``, ``cleanHtmlWhitespace()`` and ``cleanHtmlAttributeWhitespace()`` are the three
helpers the case gives you; ``getTemplatePaths()`` is the one to override when your widget's
template lives outside the paths the case already loads. Translations are stubbed by
``IDCT\Adminata\Test\StubTranslator``, which returns ``[trans]<id>[/trans]`` — assert on that
rather than on a catalogue.
