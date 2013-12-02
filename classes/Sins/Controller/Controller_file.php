<?php
/**
 * Controller for running tests.
 *
 * @package    Sins
 * @copyright  Copyright © 2013 [MrAnchovy](http://www.mranchovy.com/).
 * @license    [MIT](http://opensource.org/licenses/MIT)
**/

namespace Sins\Controller;

use Sins\TestFiles;

class Controller_file extends \Sins\Controller {

    /**
     * Implement /run/[id]/autorun.json.
    **/
    function executeApiAction_list($id = null) {
        $files = new TestFiles;
        $path = $this->local->baseDir.'test';
        return $files->recursiveScan($path);
    }

}
