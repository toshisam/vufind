<?php
/**
 *
 * @category Swissbib module
 * @package  Swissbib_Controller
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://linked.swissbib.ch  Main Page
 */


namespace Swissbib\Controller;
use VuFind\Controller\AbstractBase;

class NoProductiveSupportController extends AbstractBase {


    /**
     * GH: we don't want to support administrative services like upgrade and install in the (productive) environment
     * Even in development and test, these services are not used by swissbib because we have our own procedures
     * tailored for our purposes.
     * Once we have a better overview of the new authorization system used by VuFind (RBAC) which supports granted permissions
     * for roles this perhaps a little bit rude mechanism might be replaced.
     * use the invokable controller definitions in module config to override the default VuFind routes
     *
     * @return mixed
     */
    public function homeAction ()
    {
        return $this->forwardTo('Error','home');
    }

}