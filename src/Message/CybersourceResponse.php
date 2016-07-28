<?php

namespace Omnipay\Cybersource\Message;

use DOMDocument;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Session;

/**
 * Cybersource Response
 */
class CybersourceResponse extends AbstractResponse
{
    /** @var \stdClass  */
    private $response = null;

    private $statusOK = false;
    private $cybersourceResponseMessage = "";
    private $cybersourceResponseReasonCode = "";
    private $cybersourceRequestId = "";
    private $cybersourceRequestToken = "";
    private $cybersourceProcessorTransactionId = "";
    private $cybersourceReconciliationId = "";
    private $cybersourceAuthReconciliationId = "";
    private $cybersourceAuthRecord = "";
    private $cybersourceVerificationCode = "";
    private $cybersourceVerificationCodeRaw = "";

    /**
     * @return string
     */
    public function getCybersourceResponseMessage()
    {
        return $this->cybersourceResponseMessage;
    }

    /**
     * @return string
     */
    public function getCybersourceResponseReasonCode()
    {
        return $this->cybersourceResponseReasonCode;
    }

    /**
     * @return string
     */
    public function getCybersourceRequestId()
    {
        return $this->cybersourceRequestId;
    }

    /**
     * @return string
     */
    public function getCybersourceRequestToken()
    {
        return $this->cybersourceRequestToken;
    }

    /**
     * @return string
     */
    public function getCybersourceProcessorTransactionId()
    {
        return $this->cybersourceProcessorTransactionId;
    }

    /**
     * @return string
     */
    public function getCybersourceReconciliationId()
    {
        return $this->cybersourceReconciliationId;
    }

    /**
     * @return string
     */
    public function getCybersourceAuthReconciliationId()
    {
        return $this->cybersourceAuthReconciliationId;
    }

    /**
     * @return string
     */
    public function getCybersourceAuthRecord()
    {
        return $this->cybersourceAuthRecord;
    }

    /**
     * @return string
     */
    public function getCybersourceVerificationCode()
    {
        return $this->cybersourceVerificationCode;
    }

    /**
     * @return string
     */
    public function getCybersourceVerificationCodeRaw()
    {
        return $this->cybersourceVerificationCodeRaw;
    }



    private static $avs_codes = array(
        'A' => 'Partial match: Street address matches, but 5-digit and 9-digit postal codes do not match.',
        'B' => 'Partial match: Street address matches, but postal code is not verified.',
        'C' => 'No match: Street address and postal code do not match.',
        'D' => 'Match: Street address and postal code match.',
        'E' => 'Invalid: AVS data is invalid or AVS is not allowed for this card type.',
        'F' => 'Partial match: Card member\'s name does not match, but billing postal code matches.',
        'G' => 'Not supported: Non-U.S. issuing bank does not support AVS.',
        'H' => 'Partial match: Card member\'s name does not match, but street address and postal code match.',
        'I' => 'No match: Address not verified.',
        'K' => 'Partial match: Card member\'s name matches, but billing address and billing postal code do not match.',
        'L' => 'Partial match: Card member\'s name and billing postal code match, but billing address does not match.',
        'M' => 'Match: Street address and postal code match.',
        'N' => 'No match: Card member\'s name, street address, or postal code do not match.',
        'O' => 'Partial match: Card member\'s name and billing address match, but billing postal code does not match.',
        'P' => 'Partial match: Postal code matches, but street address not verified.',
        'R' => 'System unavailable.',
        'S' => 'Not supported: U.S. issuing bank does not support AVS.',
        'T' => 'Partial match: Card member\'s name does not match, but street address matches.',
        'U' => 'System unavailable: Address information is unavailable because either the U.S. bank does not support non-U.S. AVS or AVS in a U.S. bank is not functioning properly.',
        'V' => 'Match: Card member\'s name, billing address, and billing postal code match.',
        'W' => 'Partial match: Street address does not match, but 9-digit postal code matches.',
        'X' => 'Match: Street address and 9-digit postal code match.',
        'Y' => 'Match: Street address and 5-digit postal code match.',
        'Z' => 'Partial match: Street address does not match, but 5-digit postal code matches.',
        '1' => 'Not supported: AVS is not supported for this processor or card type.',
        '2' => 'Unrecognized: The processor returned an unrecognized value for the AVS response.',
    );

    private static $cvn_codes = array(
        'D' => 'The transaction was determined to be suspicious by the issuing bank.',
        'I' => 'The CVN failed the processor\'s data validation check.',
        'M' => 'The CVN matched.',
        'N' => 'The CVN did not match.',
        'P' => 'The CVN was not processed by the processor for an unspecified reason.',
        'S' => 'The CVN is on the card but waqs not included in the request.',
        'U' => 'Card verification is not supported by the issuing bank.',
        'X' => 'Card verification is not supported by the card association.',
        '1' => 'Card verification is not supported for this processor or card type.',
        '2' => 'An unrecognized result code was returned by the processor for the card verification response.',
        '3' => 'No result code was returned by the processor.',
    );

    private static $result_codes = array(
        '100' => 'Successful transaction.',
        '101' => 'The request is missing one or more required fields.',
        '102' => 'One or more fields in the request contains invalid data.',
        '110' => 'Only a partial amount was approved.',
        '150' => 'Error: General system failure.',
        '151' => 'Error: The request was received but there was a server timeout.',
        '152' => 'Error: The request was received, but a service did not finish running in time.',
        '200' => 'The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the Address Verification Service (AVS) check.',
        '201' => 'The issuing bank has questions about the request.',
        '202' => 'Expired card.',
        '203' => 'General decline of the card.',
        '204' => 'Insufficient funds in the account.',
        '205' => 'Stolen or lost card.',
        '207' => 'Issuing bank unavailable.',
        '208' => 'Inactive card or card not authorized for card-not-present transactions.',
        '209' => 'American Express Card Identification Digits (CID) did not match.',
        '210' => 'The card has reached the credit limit.',
        '211' => 'Invalid CVN.',
        '221' => 'The customer matched an entry on the processor\'s negative file.',
        '230' => 'The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the CVN check.',
        '231' => 'Invalid credit card number.',
        '232' => 'The card type is not accepted by the payment processor.',
        '233' => 'General decline by the processor.',
        '234' => 'There is a problem with your CyberSource merchant configuration.',
        '235' => 'The requested amount exceeds the originally authorized amount.',
        '236' => 'Processor failure.',
        '237' => 'The authorization has already been reversed.',
        '238' => 'The authorization has already been captured.',
        '239' => 'The requested transaction amount must match the previous transaction amount.',
        '240' => 'The card type sent is invalid or does not correlate with the credit card number.',
        '241' => 'The request ID is invalid.',
        '242' => 'You requested a capture, but there is no corresponding, unused authorization record.',
        '243' => 'The transaction has already been settled or reversed.',
        '246' => 'The capture or credit is not voidable because the capture or credit information has laready been submitted to your processor. Or, you requested a void for a type of transaction that cannot be voided.',
        '247' => 'You requested a credit for a capture that was previously voided.',
        '250' => 'Error: The request was received, but there was a timeout at the payment processor.',
        '481' => 'The order has been rejected by Decision Manager.',
        '520' => 'The authorization request was approved by the issuing bank but declined by CyberSource based on your Smart Authorization settings.',
    );

    private static $result_codes_es = array(
        '100' => 'Transacción realizada con éxito..',
        '101' => 'La transacción tiene uno o más campos requeridos sin llenar.',
        '102' => 'Uno o más campos en la transacción contiene datos inválidos.',
        '110' => 'Solo un monto parcial fue aprobado.',
        '150' => 'Error: Falla general del sistema.',
        '151' => 'Error: La transacción fue recibida, pero se perdió la conexión con el servidor.',
        '152' => 'Error: La transacción fue recibida, pero un servicio no termino a tiempo.',
        '200' => 'La autorización de la transacción fue aprobada por el banco establecido, pero fue rechazado por CyberSource debido a que no paso el chequeo de la dirección de verificación del servicio.',
        '201' => 'EEl banco tiene preguntas acerca de la transacción.',
        '202' => 'Tarjeta expirada.',
        '203' => 'Rechazo general de la tarjeta.',
        '204' => 'Fondos insuficientes en tu cuenta.',
        '205' => 'Tarjeta robada o perdida.',
        '207' => 'Banco no disponible.',
        '208' => 'Tarjeta inactiva o no autorizada para transacciones con tarjetas no presentes.',
        '209' => 'Identificadores de la tarjeta American Express no coinciden.',
        '210' => 'La tarjeta a llegado a su máximo limite crediticio.',
        '211' => 'CVN inválido.',
        '221' => 'El cliente posee una coincidencia en la lista negativa.',
        '230' => 'La autirizacion de la transacción fue aprobada por el banco pero denegado por CyberSource porque no paso por el chequeo de CVN.',
        '231' => 'Numero de tarjeta invalido.',
        '232' => 'El tipo de tarjeta no es aceptado por nuestro proceso de pago.',
        '233' => 'Denegación general del proceso.',
        '234' => 'Hubo un problema con la configuración de mercado de CyberSource.',
        '235' => 'El monto establecido excede el monto original autorizado.',
        '236' => 'Fallo de proceso.',
        '237' => 'La autorización ya fue corregida.',
        '238' => 'La autorización ya fue capturada.',
        '239' => 'El monto de la transacción debe estar igual a la transacción anterior.',
        '240' => 'El tipo de tarjeta enviado es invalido o no es correlativo con el número de la tarjeta de crédito.',
        '241' => 'El ID es invalido.',
        '242' => 'Se hizo una captura de transacción, pero no hay correspondiente, y una autorización de record.',
        '243' => 'La transacción ya fue revocada o acentuada.',
        '246' => 'La captura o el crédito no es anulable porque la captura o la información del crédito ya tiene una presentación en el proceso. O se hizo una transacción que no se puede aplicar.',
        '247' => 'Se hizo una transacción de crédito por la captura que fue previamente viable.',
        '250' => 'Error: la transaccion fue recibida, pero hubo un problema en el pago general.',
        '481' => 'La orden fue rechazada por el gestor de decicion.',
        '520' => 'La transaccion fue aprobada por el banco pero rechazada por CyberSource basado en la configuracion de autorizacion inteligente.',
    );


    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->goThroughResponse();
    }



    private function goThroughResponse()
    {
        if ($this->response->decision != 'ACCEPT') {
            // customize the error message if the reason indicates a field is missing
            if ($this->response->reasonCode == 101) {
                $missing_fields = 'Missing fields: ';

                if (!isset($this->response->missingField)) {
                    $missing_fields = $missing_fields.'Unknown';
                } elseif (is_array($this->response->missingField)) {
                    $missing_fields = $missing_fields.implode(', ', $this->response->missingField);
                } else {
                    $missing_fields = $missing_fields.$this->response->missingField;
                }

                $this->statusOK = false;
                $this->cybersourceResponseMessage =  $missing_fields;
                $this->cybersourceResponseReasonCode = $this->response->reasonCode;
                return;
            }

            // customize the error message if the reason code indicates a field is invalid
            if ($this->response->reasonCode == 102) {
                $invalid_fields = 'Invalid fields: ';

                if (!isset($this->response->invalidField)) {
                    $invalid_fields = $invalid_fields.'Unknown';
                } elseif (is_array($this->response->invalidField)) {
                    $invalid_fields = $invalid_fields.implode(', ', $this->response->invalidField);
                } else {
                    $invalid_fields = $invalid_fields.$this->response->invalidField;
                }

                $this->statusOK = false;
                $this->cybersourceResponseMessage =  $invalid_fields;
                $this->cybersourceResponseReasonCode = $this->response->reasonCode;
                return;
            }

            // otherwise, just throw a generic declined exception
            if ($this->response->decision == 'ERROR') {
                // note that ERROR means some kind of system error or the processor rejected invalid data - it probably doesn't mean the card was actually declined
                $this->statusOK = false;
                $this->cybersourceResponseMessage =  self::$result_codes[ $this->response->reasonCode ];
                if (Session::get('lan') == 'es') {
                    $this->cybersourceResponseMessage =  self::$result_codes_es[ $this->response->reasonCode ];
                }
                
                $this->cybersourceResponseReasonCode = $this->response->reasonCode;
            } else {
                // declined, however, actually means declined. this would be decision 'REJECT', btw.
                $this->statusOK = false;
                $this->cybersourceResponseMessage =  self::$result_codes[ $this->response->reasonCode ];
                if (Session::get('lan') == 'es') {
                    $this->cybersourceResponseMessage =  self::$result_codes_es[ $this->response->reasonCode ];
                }
                $this->cybersourceResponseReasonCode = $this->response->reasonCode;
            }
        } else {
            $this->statusOK = true;

            $this->cybersourceRequestId = $this->response->requestID;
            $this->cybersourceRequestToken = $this->response->requestToken;
            $this->cybersourceResponseReasonCode = $this->response->reasonCode;
            $this->cybersourceResponseMessage =  self::$result_codes[ $this->response->reasonCode ];

            if (isset($this->response->ccAuthReply)) {
                $this->cybersourceAuthReconciliationId = $this->response->ccAuthReply->reconciliationID;
                //$this->cybersourceAuthRecord = $this->response->ccAuthReply->authRecord;
                $this->cybersourceReconciliationId = $this->response->ccCaptureReply->reconciliationID;
            } elseif (isset($this->response->ecDebitReply)) {
                $this->cybersourceReconciliationId = $this->response->ecDebitReply->reconciliationID;
                $this->cybersourceProcessorTransactionId = $this->response->ecDebitReply->processorTransactionID;
                $this->cybersourceVerificationCode = $this->response->ecDebitReply->verificationCode;
                $this->cybersourceVerificationCodeRaw = $this->response->ecDebitReply->verificationCodeRaw;
            }
        }
    }

    public function isSuccessful()
    {
        return $this->statusOK;
    }

    public function getMessage()
    {
        return (string)$this->cybersourceResponseMessage;
    }

    public function getReasonCode()
    {
        return $this->cybersourceResponseReasonCode;
    }
}
