<?php
/**
 * experiments.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use \Exception;

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'app/init.inc.php';
$page_title = ngettext('Experiment', 'Experiments', 2);
$selected_menu = 'Experiments';
require_once 'app/head.inc.php';

// add the chemdoodle stuff if we want it
echo addChemdoodle();

try {
    $ExperimentsView = new ExperimentsView(new Experiments($_SESSION['team_id'], $_SESSION['userid']));

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        $ExperimentsView->display = $_SESSION['prefs']['display'];

        // CATEGORY FILTER
        if (isset($_GET['filter']) && !empty($_GET['filter']) && Tools::checkId($_GET['filter'])) {
            $ExperimentsView->Entity->categoryFilter = "AND status.id = " . $_GET['filter'];
            $ExperimentsView->searchType = 'filter';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $ExperimentsView->tag = $tag;
            $ExperimentsView->Entity->tagFilter = "AND experiments_tags.tag LIKE '" . $tag . "'";
            $ExperimentsView->searchType = 'tag';
        }
        // QUERY FILTER
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
            $ExperimentsView->query = $query;
            $ExperimentsView->Entity->queryFilter = "AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%' OR elabid LIKE '%$query%')";
            $ExperimentsView->searchType = 'query';
        }
        // RELATED FILTER
        if (isset($_GET['related']) && Tools::checkId($_GET['related'])) {
            $ExperimentsView->related = $_GET['related'];
            $ExperimentsView->searchType = 'related';
        }
        // ORDER
        // default by date
        $ExperimentsView->Entity->order = 'experiments.date';
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $ExperimentsView->Entity->order = 'status.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $ExperimentsView->Entity->order = 'experiments.' . $_GET['order'];
            } elseif ($_GET['order'] === 'comment') {
                $ExperimentsView->Entity->order = 'experiments_comments.datetime';
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $ExperimentsView->Entity->sort = $_GET['sort'];
            }
        }

        echo $ExperimentsView->buildShowMenu('experiments');
        echo $ExperimentsView->buildShow();

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $ExperimentsView->Entity->setId($_GET['id'], true);
        echo $ExperimentsView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $ExperimentsView->Entity->setId($_GET['id'], true);
        echo $ExperimentsView->edit();
    }

} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'app/footer.inc.php';
}
