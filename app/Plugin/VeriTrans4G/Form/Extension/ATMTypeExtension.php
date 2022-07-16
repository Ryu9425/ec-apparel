<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */
namespace Plugin\VeriTrans4G\Form\Extension;

use Eccube\Form\Type\Admin\PaymentRegisterType;
use Plugin\VeriTrans4G\Form\ExtensionUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;

/**
 * ATM支払設定画面用フォーム拡張クラス
 */
class ATMTypeExtension extends AbstractTypeExtension
{

    /**
     * コンテナ
     */
    private $container;

    /**
     * エンティティーマネージャー
     */
    private $em;

    /**
     * VT用固定値配列
     */
    private $vt4gConst;

    /**
     * コンストラクタ
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->vt4gConst = $this->container->getParameter('vt4g_plugin.const');
    }

    /**
     * フォームを作成します。
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractTypeExtension::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $Vt4gPaymentMethod = ExtensionUtil::getPaymentMethod($builder, $this->em);
        if (!ExtensionUtil::hasPaymentId($Vt4gPaymentMethod, $this->vt4gConst['VT4G_PAYTYPEID_ATM'])) {
            return;
        }
        $builder
            ->add('payment_term_day', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'vt4g_from_input_text__payment_term_day',
                    'maxlength' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["PAYMENT_TERM"]
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'vt4g_plugin.validate.not_blank'
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/^[0-9]+$/',
                            'message' => 'vt4g_plugin.validate.num',
                        ]
                    ),
                    new Range(
                        [
                            'min' => $this->vt4gConst['VT4G_FORM']["RANGE"]["MIN"]["PAYMENT_TERM"],
                            'max' => $this->vt4gConst['VT4G_FORM']["RANGE"]["MAX"]["PAYMENT_TERM"]
                        ]
                    ),
                    new Length(
                        [
                            'max' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MAX']['PAYMENT_TERM_DAY'],
                        ]
                    ),
                ],
            ])
            ->add('contents', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'vt4g_from_input_text__medium',
                    'maxlength' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["CONTENTS"],
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'vt4g_plugin.validate.not_blank'
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => "/(?:\xEF\xBD[\xA1-\xBF]|\xEF\xBE[\x80-\x9F]|[!-~])/",
                            'match'   => false,
                            'message' => 'vt4g_plugin.validate.not.halfwidth',
                        ]
                    ),
                    new Length(
                        [
                            'max' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["CONTENTS"]
                        ]
                    ),
                ],
            ])
            ->add('contents_kana', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'vt4g_from_input_text__medium',
                    'maxlength' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["CONTENTS_KANA"],
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'vt4g_plugin.validate.not_blank'
                        ]
                    ),
                    new Length(
                        [
                            'max' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["CONTENTS_KANA"]
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/^[ァ-ヶｦ-ﾟー]+$/u',
                            'message' => 'vt4g_plugin.validate.kana',
                        ]
                    ),
                ],
            ])
            ->add('order_mail_title1', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'vt4g_from_input_text__large',
                    'maxlength' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["ORDER_MAIL_TITLE1"],
                ],
                'mapped' => false,
                'constraints' => [
                    new Length(
                        [
                            'max' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["ORDER_MAIL_TITLE1"]
                        ]
                    ),
                ],
            ])
            ->add('order_mail_body1', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'vt4g_from_input_textarea',
                    'maxlength' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["ORDER_MAIL_BODY1"],
                ],
                'mapped' => false,
                'constraints' => [
                    new Length(
                        [
                            'max' => $this->vt4gConst['VT4G_FORM']["LENGTH"]["MAX"]["ORDER_MAIL_BODY1"]
                        ]
                    ),
                ],
            ]);
    }

    /**
     * 拡張元クラスを取得します。
     * {@inheritDoc}
     * @see \Symfony\Component\Form\FormTypeExtensionInterface::getExtendedType()
     */
    public function getExtendedType()
    {
        return PaymentRegisterType::class;
    }

}
