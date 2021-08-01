<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Form\Type;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use BabDev\MoneyBundle\Form\DataTransformer\MoneyToLocalizedStringTransformer;
use Money\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Alternative money form type supporting a `Money\Money` object as a data input.
 *
 * Class is based on \Symfony\Component\Form\Extension\Core\Type\MoneyType
 */
final class MoneyType extends AbstractType
{
    private FormatterFactoryInterface $formatterFactory;
    private ParserFactoryInterface $parserFactory;
    private Currency $defaultCurrency;

    /**
     * @var array<string, array<string, string>>
     */
    private static array $patterns = [];

    /**
     * @phpstan-param non-empty-string $defaultCurrency
     */
    public function __construct(FormatterFactoryInterface $formatterFactory, ParserFactoryInterface $parserFactory, string $defaultCurrency)
    {
        $this->formatterFactory = $formatterFactory;
        $this->parserFactory = $parserFactory;
        $this->defaultCurrency = new Currency($defaultCurrency);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Values used in HTML5 number inputs should be formatted as in "1234.5", ie. 'en' format without grouping,
        // according to https://www.w3.org/TR/html51/sec-forms.html#date-time-and-number-formats
        $builder
            ->addViewTransformer(new MoneyToLocalizedStringTransformer(
                $this->formatterFactory,
                $this->parserFactory,
                $options['currency'],
                $options['scale'],
                $options['grouping'],
                $options['rounding_mode'],
                $options['html5'] ? 'en' : null
            ))
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['money_pattern'] = self::getPattern($options['currency']);

        if ($options['html5']) {
            $view->vars['type'] = 'number';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'scale' => 2,
                'grouping' => false,
                'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
                'currency' => $this->defaultCurrency,
                'compound' => false,
                'html5' => false,
                'invalid_message' => 'Please enter a valid money amount.',
            ]
        );

        $resolver->setAllowedValues(
            'rounding_mode',
            [
                \NumberFormatter::ROUND_FLOOR,
                \NumberFormatter::ROUND_DOWN,
                \NumberFormatter::ROUND_HALFDOWN,
                \NumberFormatter::ROUND_HALFEVEN,
                \NumberFormatter::ROUND_HALFUP,
                \NumberFormatter::ROUND_UP,
                \NumberFormatter::ROUND_CEILING,
            ]
        );

        $resolver->setAllowedTypes('scale', 'int');
        $resolver->setAllowedTypes('html5', 'bool');
        $resolver->setAllowedTypes('currency', Currency::class);

        $resolver->setNormalizer(
            'grouping',
            static function (Options $options, $value) {
                if ($value && $options['html5']) {
                    throw new LogicException('Cannot use the "grouping" option when the "html5" option is enabled.');
                }

                return $value;
            }
        );
    }

    public function getBlockPrefix(): string
    {
        return 'money';
    }

    /**
     * Returns the pattern for this locale in UTF-8.
     *
     * The pattern contains the placeholder "{{ widget }}" where the HTML tag should be inserted.
     */
    private static function getPattern(Currency $currency): string
    {
        $currencyCode = $currency->getCode();

        $locale = \Locale::getDefault();

        if (!isset(self::$patterns[$locale])) {
            self::$patterns[$locale] = [];
        }

        if (!isset(self::$patterns[$locale][$currencyCode])) {
            $format = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $pattern = $format->formatCurrency(123.0, $currencyCode);

            // the spacings between currency symbol and number are ignored, because
            // a single space leads to better readability in combination with input
            // fields

            // the regex also considers non-break spaces (0xC2 or 0xA0 in UTF-8)

            preg_match('/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u', $pattern, $matches);

            if (!empty($matches[1])) {
                self::$patterns[$locale][$currencyCode] = $matches[1].' {{ widget }}';
            } elseif (!empty($matches[2])) {
                self::$patterns[$locale][$currencyCode] = '{{ widget }} '.$matches[2];
            } else {
                self::$patterns[$locale][$currencyCode] = '{{ widget }}';
            }
        }

        return self::$patterns[$locale][$currencyCode];
    }
}
