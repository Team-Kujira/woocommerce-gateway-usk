<?php


/**
 *
 * This class defines all code necessary to interact with the Kujira blockchain
 *
 * @since      1.0.0
 * @package    Kujira
 * @subpackage Kujira/includes
 * @author     codehans <94654388+codehans@users.noreply.github.com>
 */
class Kujira_Chain_Tx_Result
{

    public $error;
    public $code;
    public $hash;
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function __construct($response)
    {
        $this->error = $response->deliver_tx->log;
        $this->error = $response->check_tx->code > 0 ? $response->check_tx->log : $this->error;

        $this->code = $response->deliver_tx->code;
        $this->code = $response->check_tx->code > 0 ? $response->check_tx->code : $this->code;

        $this->hash = $response->hash;
    }

    public function success()
    {
        return $this->code == 0;
    }
}
