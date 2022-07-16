<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */

namespace Plugin\VeriTrans4G\Service\Payment;

use Eccube\Entity\Customer;
use Plugin\VeriTrans4G\Entity\Vt4gOrderPayment;
use Plugin\VeriTrans4G\Form\Type\Shopping\PaymentAmazonPayType;

// Amazon Pay決済関連処理
class AmazonPayService extends BaseService
{
    /**
     * クレジットカード情報入力フォームを生成
     * @param  array $paymentInfo クレジットカード決済設定
     * @return object             クレジットカード情報入力フォーム
     */
    public function createAmazonPayForm($paymentInfo)
    {
        return $this->container->get('form.factory')
            ->create(PaymentAmazonPayType::class, compact('paymentInfo'));
    }


    /**
     * クレジットカード決済処理
     * (MDKトークン利用・再取引)
     *
     * @param  object  $inputs  フォーム入力データ
     * @param  array   $payload 追加参照データ
     * @param  array   &$error  エラー
     * @return boolean          決済が正常終了したか
     */
    public function commitNormalPayment($inputs, $payload, &$error)
    {
       
        // 決済金額 (整数値で設定するため小数点以下切り捨て)
        $amount = floor($payload['order']->getPaymentTotal());
        $is_with_capture = $inputs->get('payment_amazon_pay')['withCapture'];
        $is_suppress_shipping_address_view = $inputs->get('payment_amazon_pay')['suppressShippingAddressView'];
        $note_to_buyer = $inputs->get('payment_amazon_pay')['noteToBuyer'];
        
        $success_url = "https://apparel-oroshitonya.com/shopping/amazonpay/complete/".$payload['order']->getId();
        $cancel_url = "https://apparel-oroshitonya.com/card";
        $error_url = "https://apparel-oroshitonya.com/shopping/error";
        $authorizePushUrl = "https://apparel-oroshitonya.com/shopping";
        $cancelPushUrl = "https://apparel-oroshitonya.com/shopping";
        $capturePushUrl="https://apparel-oroshitonya.com/shopping/confirm";


        // カード情報登録フラグ
        $order_id = "amazonpay" . time();

        $request_data = new \AmazonpayAuthorizeRequestDto();
        $request_data->setOrderId($order_id);
        $request_data->setAmount($amount);
        $request_data->setWithCapture(0);
        $request_data->setSuppressShippingAddressView($is_suppress_shipping_address_view);
        $request_data->setNoteToBuyer($note_to_buyer);

        $request_data->setSuccessUrl($success_url);
        $request_data->setCancelUrl($cancel_url);
        $request_data->setErrorUrl($error_url);   

        $request_data->setAuthorizePushUrl($authorizePushUrl);
        $request_data->setCancelPushUrl($cancelPushUrl);
        $request_data->setCapturePushUrl($capturePushUrl);            

        /**
         * 実施
         */
        $transaction = new \TGMDK_Transaction();
        $response_data = $transaction->execute($request_data);

        //予期しない例外
        if (!isset($response_data)) {
            $page_title = ERROR_PAGE_TITLE;
        //想定応答の取得
        } else {
            define('TXN_SUCCESS_CODE', 'success');
            $page_title = NORMAL_PAGE_TITLE;
        
            /**
             * 取引ID取得
             */
            $result_order_id = $response_data->getOrderId();
            /**
             * 結果コード取得
             */
            $txn_status = $response_data->getMStatus();
            /**
             * 詳細コード取得
             */
            $txn_result_code = $response_data->getVResultCode();
            /**
             * エラーメッセージ取得
             */
            $error_message = $response_data->getMerrMsg();
        
            // ログ
            $test_log = "<!-- vResultCode=" . $txn_result_code . " -->";
            if (TXN_SUCCESS_CODE === $txn_status) {

                $orderId = $request_data->getOrderId();
                // 決済データを登録
                $payment = [
                    'orderId'    => $orderId,
                    'payStatus'  => '',
                    'cardType'   => '',
                    'cardAmount' => $amount,
                    'withCapture' => $payload['paymentInfo']['withCapture']
                ];
                $this->setOrderPayment($payload['order'], $payment, [], [], 'Nan');
                
                $this->handleNormalResponse($response_data,$payload['order'], $error);
                $this->em->commit();
                $isMpi = $payload['paymentInfo']['mpi_flg'];
                $this->mdkLogger->info(
                    sprintf(
                        $isMpi ? trans('vt4g_plugin.payment.shopping.mdk.start.mpi') : trans('vt4g_plugin.payment.shopping.mdk.start'),
                        $this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_80']
                    )
                );
                // 成功
                $response_html = $response_data->getResponseContents();
                header("Content-type: text/html; charset=UTF-8");
                echo $response_html . $test_log;
                exit;
            } else {
                // エラーページ表示
                $title = "エラーページ";
                $html = $this->createResultPage($response_data, $title);
                print $html . $test_log;
                exit;
            }
        }

        // // 再取引決済の場合に元取引IDのバリデーションを行う
        // if ($isReTrade && !$this->isValidReTradeOrder($inputs->get('payment_order_id'), $payload['user']->getid())) {
        //     $error['payment'] = trans('vt4g_plugin.shopping.credit.mErrMsg.retrade').'<br/>';
        //     return false;
        // }

        // // MDKリクエスト生成・レスポンスのハンドリングに使用するデータ
        // $sources = array_merge(
        //     compact('isMpi'),
        //     compact('useAccountPayment'),
        //     compact('isReTrade'),
        //     compact('isAfterAuth'),
        //     compact('amount'),
        //     compact('inputs'),
        //     compact('doRegistCardinfo'),
        //     $payload
        // );

        // // MDKリクエストを生成
        // $mdkRequest = $this->makeMdkRequest($sources);

        // $orderId = $mdkRequest->getOrderId();
        // $sources['orderid'] = $orderId;

        // $cardType = $inputs->get('payment_credit')['payment_type'] ?? '';
        // if ($isAccountPayment) {
        //     $cardType = $inputs->get('payment_credit_account')['payment_type'] ?? '';
        // }
        // if ($isReTrade) {
        //     $cardType = $inputs->get('payment_credit_one_click')['payment_type'] ?? '';
        // }

        // // 決済データを登録
        // $payment = [
        //     'orderId'    => $orderId,
        //     'payStatus'  => '',
        //     'cardType'   => $cardType,
        //     'cardAmount' => $amount,
        //     'withCapture' => $payload['paymentInfo']['withCapture']
        // ];
        // $this->setOrderPayment($payload['order'], $payment, [], [], $inputs->get('token_id'));

        // $this->em->commit();

        // $this->mdkLogger->info(
        //     sprintf(
        //         $isMpi ? trans('vt4g_plugin.payment.shopping.mdk.start.mpi') : trans('vt4g_plugin.payment.shopping.mdk.start'),
        //         $this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_10']
        //     )
        // );


        // $mdkTransaction = new \TGMDK_Transaction();
        // $mdkResponse = $mdkTransaction->execute($mdkRequest);

        // return $this->handleMdkResponse($mdkResponse, $sources, $error);
    }

    /**
     * MDKリクエストのレスポンスのハンドリング
     * (各パターン共通処理)
     *
     * @param  object  $response MDKリクエストのレスポンス
     * @param  array   $sources  ハンドリングに必要なデータ
     * @param  array   &$error   エラー表示用配列
     * @return boolean           レスポンスを正常に処理したかどうか
     */
    private function handleNormalResponse($response,$Order, &$error)
    {
        // // 通常クレジットカード決済の正常終了
        $this->paymentResult['isOK'] = true;
        // // 取引ID取得
        $this->paymentResult['orderId'] = $response->getOrderId();
        // // マスクされたクレジットカード番号
        // // $this->paymentResult['cardNumber'] = $response->getReqCardNumber();
        // // 支払い方法・支払い回数
        // $jpo = $response->getReqJpoInformation();
        // $this->paymentResult['paymentType'] = substr($jpo, 0, 2);
        // $this->paymentResult['paymentCount'] = substr($jpo, 2);

        // // 決済状態を保持
        // $this->paymentResult['payStatus'] = $sources['paymentInfo']['withCapture']
        //     ? $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']
        //     : $this->vt4gConst['VT4G_PAY_STATUS']['AUTH']['VALUE'];
        // $this->paymentResult['mpiHosting'] = false;
        // $this->paymentResult['withCapture'] = $sources['paymentInfo']['withCapture'];

        $this->mdkLogger->info(print_r($this->paymentResult, true));

        // // 正常終了の場合
        // if ($this->paymentResult['isOK']) {
        //     // ベリトランス会員ID決済の場合
        //     if ($sources['useAccountPayment'] && !empty($sources['user']) && $sources['doRegistCardinfo']) {
        //         // ベリトランス会員IDをテーブルに保存
        //         $accountId = $response->getPayNowIdResponse()->getAccount()->getAccountId();
        //         $this->saveAccountId($sources['user']->getId(), $accountId);
        //     }

        // $this->completeAmazonOrder($Order);

        //     // 受注完了処理
        //     if (!$isCompleted) {
        //         $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.complete'));
        //         $error['payment'] = $this->defaultErrorMessage;
        //         return false;
        //     }
        // }

        return true;
    }

    /**
     * 受注完了処理
     *
     * @param  array $order 注文データ
     * @return void
     */
    public function completeAmazonOrder($response_suc, $order)
    {
        
        $this->paymentResult['isOK'] = true;
        $this->paymentResult['payStatus'] = 1;
        $this->paymentResult['orderId'] = $response_suc->get('orderId');
        if (!$this->paymentResult['isOK']) {
            return false;
        }

        // 決済情報 (memo05)
        $payment = $this->paymentResult;
        
        // メール情報 (memo06)
        $this->mailData = [];
        $this->setMailTitle($this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_80']);
        $this->setMailInfo('決済取引ID', $this->paymentResult['orderId']);
        $paymentMethod = $this->util->getPaymentMethod($order->getPayment()->getId());
        $this->setMailAdminSetting($paymentMethod);

        // 決済変更ログ情報 (plg_vt4g_order_logテーブル)
        $this->setLog($order);

        // // 受注ステータス更新
        // if (!$this->setNewOrderStatus($order,$payment['withCapture'])) {
        //     return false;
        // }

        // 受注完了処理
        $this->completeOrder($order, $payment, $this->logData, $this->mailData);

        return true;
    }
    
    /**
     * キャンセル処理
     *
     * @param  array $payload キャンセル処理に使用するデータ
     * @return array          キャンセル処理結果データ
     */
    public function operateCancel($payload)
    {
        
        // キャンセル共通処理
        list($operationResult, $mdkResponse) = parent::operateCancel($payload);

        // ログの出力
        $this->mdkLogger->info(print_r($operationResult, true));

        if ($operationResult['isOK']) {
            // $memo10 = unserialize($payload['orderPayment']->getMemo10());
            // if ($memo10 !== false && !empty($memo10['card_amount'])) {
            //     $amount = number_format($memo10['card_amount']);
            //     $this->setLogInfo('取消金額', $amount);

                $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.order.id'). $operationResult['orderId']);
                // $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.cancel.amount'). $amount);
            // }
        }

        return $operationResult;
    }
    
    /**
     * 売上処理
     *
     * @param  array $payload 売上処理に使用するデータ
     * @return array          売上処理結果データ
     */
    public function operateCapture($payload)
    {
        // 決済ステータス
        $paymentStatus = $payload['orderPayment']->getMemo04();
        // 決済申込時のレスポンス
        $prevPaymentResult = unserialize($payload['orderPayment']->getMemo05());

        // レスポンス初期化
        $authOperationResult = $this->initPaymentResult();

        // 決済ステータスが売上の場合
        if ($paymentStatus == $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']) {
            // 現在の取引ID
            $originPaymentOrderId = $payload['orderPayment']->getMemo01();

            // 決済ログ情報を初期化
            $this->logData = [];

            // 再決済
            $authOperationResult = $this->operateAuth($payload, $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']);

            if (!$authOperationResult['isOK']) {
                $authOperationResult['message'] = $authOperationResult['vResultCode'].':'.$authOperationResult['mErrMsg'];

                $this->mdkLogger->info(print_r($authOperationResult, true));

                return $authOperationResult;
            }

            // 更新処理
            $this->updateByAdmin($payload['orderPayment'], $authOperationResult);
            $this->em->flush();

            $this->logData = [];

            // 再決済後の取引ID
            $newPaymentOrderId = $authOperationResult['orderId'];

            // 再決済前の取引を取消
            $payload['orderPayment']->setMemo01($originPaymentOrderId);
            $cancelOperationResult = $this->operateCancel($payload);

            // 再決済後の取引IDを再設定
            $payload['orderPayment']->setMemo01($newPaymentOrderId);
            // memo10更新
            $memo10 = unserialize($payload['orderPayment']->getMemo10());
            $memo10['card_amount'] = floor($payload['order']->getPaymentTotal());
            $payload['orderPayment']->setMemo10(serialize($memo10));

            // キャンセル処理が異常終了の場合
            if (!$cancelOperationResult['isOK']) {
                $this->mdkLogger->info(print_r($cancelOperationResult, true));

                return $cancelOperationResult;
            }

            $this->mdkLogger->info(print_r($authOperationResult, true));

            return $authOperationResult;
        }

        $payId   = $payload['orderPayment']->getMemo03();
        $payName = $this->util->getPayName($payId);

        $this->mdkLogger->info(
            sprintf(
                trans('vt4g_plugin.admin.order.credit.capture.start'),
                $payName
            )
        );

        // レスポンス初期化
        $operationResult = $this->initPaymentResult();
        // 取引ID
        $paymentOrderId = $payload['orderPayment']->getMemo01();
        // memo01から取得できない場合
        if (empty($paymentOrderId)) {
            // 決済申込時のレスポンスから取得できない場合
            if (empty($prevPaymentResult['orderId'])) {
                $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.order.id'));
                $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');
                return $operationResult;
            }
            // 決済申込時の結果から取得
            $paymentOrderId = $prevPaymentResult['orderId'];
        }

        $mdkRequest = new \AmazonpayCaptureRequestDto();

        // 取引ID
        $mdkRequest->setOrderId($paymentOrderId);
        // 決済金額
        $mdkRequest->setAmount(floor($payload['order']->getPaymentTotal()));

        $mdkTransaction = new \TGMDK_Transaction();
        $mdkResponse = $mdkTransaction->execute($mdkRequest);

        // レスポンス検証
        if (!isset($mdkResponse)) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.payment.shopping.mdk.error'));
            $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');

            $this->mdkLogger->info(print_r($operationResult, true));

            return $operationResult;
        }

        // 結果コード
        $operationResult['mStatus'] = $mdkResponse->getMStatus();
        // 詳細コード
        $operationResult['vResultCode'] = $mdkResponse->getVResultCode();
        // エラーメッセージ
        $operationResult['mErrMsg'] = $mdkResponse->getMErrMsg();

        // 異常終了レスポンスの場合
        if ($operationResult['mStatus'] === $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['NG']) {
            $operationResult['message']  = $operationResult['vResultCode'].':';
            $operationResult['message'] .= $operationResult['mErrMsg'];

            $this->mdkLogger->info(print_r($operationResult, true));

            return $operationResult;
        }

        $operationResult['isOK']        = true;
        // 取引ID
        $operationResult['orderId']     = $mdkResponse->getOrderId();
        // 決済サービスタイプ
        $operationResult['payStatus']   = $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE'];

        // 決済変更ログ情報を設定
        $this->logData = [];
        $this->setLog($payload['order'], $operationResult);

        // 変更後の金額をログ出力
        $amount = number_format(floor($payload['order']->getPaymentTotal()));
        $this->setLogInfo('売上確定金額', $amount);

        $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.order.id'). $operationResult['orderId']);
        $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.capture.amount'). $amount);

        // ログの出力
        $this->mdkLogger->info(print_r($operationResult, true));

        return $operationResult;
    }


     /**
     * ログ出力内容を設定
     *
     * @param  object $order Orderクラスインスタンス
     * @return void
     */
    private function setLog($order, $paymentResult = null)
    {
        if (is_null($paymentResult)) {
            $paymentResult = $this->paymentResult;
        }

        $this->timeKey = '';

        $payId = $this->util->getPayId($order->getPayment()->getId());
        $payName = $this->util->getPayName($payId);
        // $payStatusName = $this->util->getPaymentStatusName($paymentResult['payStatus']);

        $this->setLogInfo('決済取引ID', $paymentResult['orderId']);
        $this->setLogInfo($payName, sprintf(
            $this->isPaymentRecv ? trans('決済結果通知受信') : trans('成功')
        ));
    }

    /**
     * ダミーモードを判定します.
     */
    public function createResultPage($response, $title) {

        $html = '<html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Content-Language" content="ja" />
        <title>'.$title.'</title>
        <link href="../css/style.css" rel="stylesheet" type="text/css">
        </head>
        <body>
        <div class="system-message">
        <font size="2">
        本画面はVeriTrans4G Amazon Payの取引サンプル画面です.<br/>
        お客様ECサイトのショッピングカートとVeriTrans4Gとを連動させるための参考、例としてご利用ください.<br/>
        </font>
        </div>
        
        <div class="lhtitle">Amazon Pay:取引結果</div>
        <table border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td class="rititletop">取引ID</td>
            <td class="rivaluetop">'.$response->getOrderId().'<br/></td>
            </tr>
            <tr>
            <td class="rititle">取引ステータス</td>
            <td class="rivalue">'.$response->getMStatus().'</td>
            </tr>
            <tr>
            <td class="rititle">結果コード</td>
            <td class="rivalue">'.$response->getVResultCode().'</td>
            </tr>
            <tr>
            <td class="rititle">結果メッセージ</td>
            <td class="rivalue">'.$response->getMerrMsg().'</td>
            </tr>
        </table>
        <br/>
        
        <a href="../PaymentMethodSelect.php">決済サンプルのトップメニューへ戻る</a>&nbsp;&nbsp;
        
        <hr>
        <img alt="VeriTransロゴ" src="../WEB-IMG/VeriTransLogo_WH.png">&nbsp; Copyright &copy; VeriTrans Inc. All rights reserved
        
        
        </body></html>';
    
        return $html;
    }




    /**
     * ダミーモードを判定します.
     * @return boolean||null trueとnull:ダミーモード、false:本番モード
     */
    protected function isDummyMode()
    {
        $subData = $this->util->getPluginSetting();
        if (isset($subData)) {
            return $subData['dummy_mode_flg'] == '1';
        } else {
            return true;
        }
    }
}
