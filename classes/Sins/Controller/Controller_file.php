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
use Exception;

class Controller_file extends \Sins\Controller {


    /**
     * Implement `file/[id]/list.json`.
    **/
    protected function executeApiAction_list($id = null)
    {
        $files = new TestFiles;

        $path = realpath($id === '.' ? $this->local->testBaseDir : $this->local->testBaseDir . DIRECTORY_SEPARATOR . $id);

        // path must exist and be under the base directory - defeat directory traversal with ..
        if (empty($path) || strpos($path, $this->local->testBaseDir) !== 0 ) {
            $this->response->status = 404;
            $this->response->body = array(
                'status' => 'error',
                'error'  => array('message' => 'Invalid path'),
            );
            return;
        }

        $files->stripBase = $this->local->testBaseDir . DIRECTORY_SEPARATOR;

        $testFiles = $files->scan($path);

        $this->response->body = array(
            'status' => 'ok',
            'files'  => $testFiles,
        );
    }

    /**
     * Implement /file/[id]/list.json.
    **/
    protected function list_sins_tests()
    {
        $files = new TestFiles;

        $path = $this->local->baseDir.'test';
        $testFiles = $files->scan($path);

        $path = $this->local->baseDir.'simpletest/test';
        $files->allFiles  = true;
        $files->recursive = false;

        $testFiles = array_merge($testFiles, $files->scan($path));

        $this->response->body = array(
            'status' => 'ok',
            'files'  => $testFiles,
        );
    }

}
