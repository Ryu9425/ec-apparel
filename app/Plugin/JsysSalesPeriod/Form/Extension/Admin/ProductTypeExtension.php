<?php
namespace Plugin\JsysSalesPeriod\Form\Extension\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Eccube\Form\Type\Admin\ProductType;
use Eccube\Form\Type\ToggleSwitchType;

/**
 * Admin ProductType extension.
 * @author manabe
 *
 */
class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractTypeExtension::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = $builder->getType()->getBlockPrefix();

        $builder
            ->add('jsys_sales_period_sale_start', DateTimeType::class, [
                'required' => false,
                'input'    => 'datetime',
                'widget'   => 'single_text',
                'format'   => 'yyyy/MM/dd HH:mm:ss',
                'attr'     => [
                    'class'       => 'datetimepicker-input',
                    'data-target' => '#' . $prefix . '_jsys_sales_period_sale_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('jsys_sales_period_sale_finish', DateTimeType::class, [
                'required' => false,
                'input'    => 'datetime',
                'widget'   => 'single_text',
                'format'   => 'yyyy/MM/dd HH:mm:ss',
                'attr'     => [
                    'class'       => 'datetimepicker-input',
                    'data-target' => '#' . $prefix . '_jsys_sales_period_sale_finish',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('jsys_sales_period_display_before_sale', ToggleSwitchType::class)
            ->add('jsys_sales_period_display_finished', ToggleSwitchType::class);

        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\FormTypeExtensionInterface::getExtendedType()
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }


    /**
     * 初期値を設定します。
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data  = $event->getData();

        $start = $data['jsys_sales_period_sale_start'];
        if ($start) {
            $data['jsys_sales_period_sale_start'] = $this->getSalePeriodDate($start);
        }

        $finished = $data['jsys_sales_period_sale_finish'];
        if ($finished) {
            $data['jsys_sales_period_sale_finish'] = $this->getSalePeriodDate($finished);
        }

        $event->setData($data);
    }

    /**
     * 値の検証を追加します。
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form   = $event->getForm();
        $min    = new \DateTime('1970/01/01 00:00:00');
        $errors = [];

        // 販売開始日時：古すぎる日付が入力されている場合はエラー
        $start = $form['jsys_sales_period_sale_start']->getData();
        if ($start && ($start <= $min)) {
            $errors['jsys_sales_period_sale_start'][] = trans(
                'jsys_sales_period.admin.product.edit.error.date_too_old'
            );
        }

        // 販売終了日時：古すぎる日付が入力されている場合はエラー
        $finished = $form['jsys_sales_period_sale_finish']->getData();
        if ($finished && ($finished <= $min)) {
            $errors['jsys_sales_period_sale_finish'][] = trans(
                'jsys_sales_period.admin.product.edit.error.date_too_old'
            );
        }

        // 販売終了日時：販売開始日以前の場合はエラー
        if ($start && $finished && ($start >= $finished)) {
            $errors['jsys_sales_period_sale_finish'][] = trans(
                'jsys_sales_period.admin.product.edit.error.date_reversal'
            );
        }

        // 販売前表示：販売前にも表示したいのに販売開始日時が未入力の場合はエラー
        $displayBefore = $form['jsys_sales_period_display_before_sale']->getData();
        if ($displayBefore && empty($start)) {
            $errors['jsys_sales_period_display_before_sale'][] = trans(
                'jsys_sales_period.admin.product.edit.error.sale_start_empty'
            );
        }

        // 終了後表示：販売終了後にも表示したいのに販売終了日時が未入力の場合はエラー
        $displayFinished = $form['jsys_sales_period_display_finished']->getData();
        if ($displayFinished && empty($finished)) {
            $errors['jsys_sales_period_display_finished'][] = trans(
                'jsys_sales_period.admin.product.edit.error.sale_finish_empty'
            );
        }

        // エラーをフォームへ追加
        foreach ($errors as $column => $error) {
            foreach ($error as $message) {
                $form[$column]->addError(new FormError($message));
            }
        }
    }


    /**
     * 日付として正しいか調べます。
     * @param string $value
     * @param string $format
     * @return boolean
     */
    private function validationDate($value, $format = 'Y/m/d H:i:s')
    {
        if (empty($value)) {
            return false;
        }
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) == $value;
    }

    /**
     * datetimepickerでエラーにならないように補正したデータを取得します。
     * @param string $value
     * @return string
     */
    private function getSalePeriodDate($value)
    {
        /*
         * 0123/01/01 00:00:00のような日付で検証失敗になった場合、
         * Maximum call stack size exceededの発生とdatetimepikerが動かない時があった。
         * そのため、一定より過去や妥当ではない日付は検証失敗になる日付を設定し直す。
         */
        $min = new \DateTime('1970/01/01 00:00:00');

        // 妥当でない日付は検証失敗になる値を返す
        if (!$this->validationDate($value)) {
            return $min->format('Y/m/d H:i:s');
        }
        // 古すぎる日付は検証失敗になる値を返す
        $dt = new \DateTime($value);
        if ($dt < $min) {
            return $min->format('Y/m/d H:i:s');
        }

        // 正常な日付のためそのまま返す
        return $value;
    }

}
