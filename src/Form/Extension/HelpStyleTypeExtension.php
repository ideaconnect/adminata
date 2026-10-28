<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IDCT\Adminata\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * How a field's help is drawn: `plain`, the small grey line under the control, or `box`, a
 * lighter box under the control set in the form's own text size, for forms whose help is
 * part of the reading (recomaty/recomaty-panel#682).
 *
 * The `help_style` option is inherited. A form or field without one of its own takes its
 * parent's, so an admin sets it once for its whole form — the root form's options, from
 * `configureFormOptions()` — and its sub-forms and collection entries (the prototype too)
 * follow; a field can still pick the other style for itself. A form at the top with none
 * takes `adminata.options.help_style`.
 *
 * The resolved style is the view variable `help_style`, which `form_help` of
 * `@Adminata/Form/form_admin_fields.html.twig` reads to pick `adm-help` or `adm-help-box`.
 */
final class HelpStyleTypeExtension extends AbstractTypeExtension
{
    public const string PLAIN = 'plain';

    public const string BOX = 'box';

    /**
     * @var list<string>
     */
    public const array STYLES = [self::PLAIN, self::BOX];

    /**
     * @param string $default the style of a form at the top that names none, `adminata.options.help_style`
     */
    public function __construct(
        private readonly string $default = self::PLAIN,
    ) {
        if (!\in_array($default, self::STYLES, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The default help style must be one of "%s", got "%s".',
                implode('", "', self::STYLES),
                $default,
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('help_style', null)
            ->setAllowedValues('help_style', [null, ...self::STYLES])
            ->setInfo('help_style', 'How the help is drawn: "plain" or "box"; null takes the parent form\'s, and the top form\'s default is adminata.options.help_style.');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $own = $options['help_style'] ?? null;

        $view->vars['help_style'] = \is_string($own) ? $own : $this->inherited($view);
    }

    /**
     * @return string[]
     *
     * @phpstan-return class-string<FormTypeInterface>[]
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    /**
     * The parent's resolved style. A parent's `buildView()` has run before its children's
     * views are created, the collection prototype's included, so the variable is there.
     */
    private function inherited(FormView $view): string
    {
        $parentStyle = $view->parent?->vars['help_style'] ?? null;

        return \is_string($parentStyle) ? $parentStyle : $this->default;
    }
}
