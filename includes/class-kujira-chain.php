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
class Kujira_Chain
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function broadcast(string $tx)
    {

        $body = array(
            'jsonrpc'    => '2.0',
            'id'   => 'usk-pay',
            'method' => 'broadcast_tx_commit',
            'params' => array('tx' => $tx),
        );

        $args = array(
            'body'        => wp_json_encode($body),
            'timeout'     => 60,
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => [
                'Content-Type' => 'application/json',
            ],
            'cookies'     => array(),
        );

        $response = wp_remote_post('https://rpc.kaiyo.kujira.setten.io', $args);


        // curl --data-binary '{"jsonrpc":"2.0","id":"anything","method":"broadcast_tx_commit","params": {"tx": "AQIDBA=="}}' -H 'content-type:text/plain;' http://localhost:26657

        return new Kujira_Chain_Tx_Result(json_decode($response["body"])->result);
    }
}
