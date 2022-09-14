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
    public static function broadcast(Kujira_Chain_Tx $tx)
    {

        $body = array(
            'jsonrpc'    => '2.0',
            'id'   => 'usk-pay',
            'method' => 'broadcast_tx_commit',
            'params' => '{"tx": "' . $tx . '"}',
        );

        $args = array(
            'body'        => $body,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'cookies'     => array(),
        );

        $response = wp_remote_post('http://your-contact-form.com', $args);


        // curl --data-binary '{"jsonrpc":"2.0","id":"anything","method":"broadcast_tx_commit","params": {"tx": "AQIDBA=="}}' -H 'content-type:text/plain;' http://localhost:26657

        return $tx;
    }
}
