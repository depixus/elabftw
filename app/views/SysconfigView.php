<?php
/**
 * \Elabftw\Elabftw\SysconfigView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * HTML for the sysconfig page
 */
class SysconfigView
{
    /** instance of Update */
    public $Update;

    /** instance of Logs */
    private $Logs;

    /** instance of TeamsView */
    public $TeamsView;

    /**
     * Constructor
     *
     * @param Update $update
     * @param Logs $logs
     * @param TeamsView $teamsview
     */
    public function __construct(Update $update, Logs $logs, TeamsView $teamsview)
    {
        $this->Update = $update;
        $this->Logs = $logs;
        $this->TeamsView = $teamsview;
    }

    /**
     * Output HTML for displaying the test email block
     *
     * @return string $html
     */
    public function testemailShow()
    {
        $html = "<div class='box'>";
        $html .= "<label class='block' for='testemailEmail'>" . _('Send a test email') . "</label>";
        $html .= " <input class='clean-form col-3-form' type='email' placeholder='you@email.com' id='testemailEmail' />";
        $html .= "<button id='testemailButton' onClick='testemailSend()' class='button'>" . _('Send') . "</button>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Output HTML for displaying the logs
     *
     * @return string $html
     */
    public function logsShow()
    {
        $logsArr = $this->Logs->read();
        $html = "<div id='logsDiv'>";
        $html .= "<div class='well'><ul class='clean-list'>";
        if (empty($logsArr)) {
            $html .= "<li>" . _('Nothing to display') . ".</li>";
        } else {
            foreach ($logsArr as $logs) {
                $html .= "<li>✪ " . $logs['datetime'] . " [" . $logs['type'] . "] " .
                    $logs['body'] . " (" . $logs['user'] . ")</li>";
            }
        }
        $html .= "</ul></div>";
        $html .= "<div class='submitButtonDiv'>";
        $html .= "<button id='logsDestroyButton' onClick='logsDestroy()' class='button button-delete'>" .
            ('Clear all logs') . "</button></div></div>";

        return $html;
    }
}
