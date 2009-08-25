<?php
/* SVN FILE: $Id$ */
/**
 * Abstract Toolbar helper.  Provides Base methods for content
 * specific debug toolbar helpers.  Acts as a facade for other toolbars helpers as well.
 *
 * helps with development.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       debug_kit
 * @subpackage    debug_kit.views.helpers
 * @since         v 1.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class OrderHelper extends AppHelper {

    public $helpers = array('Form');

    function selectBox($id = null, $position = null, $positionsList = array(), $field = 'position', $url = array('action' => 'order', 'admin' => false)) {
        $output = "";
        $output.= $this->Form->create(null, array('url' => $url, 'id' => "OrderedListSelectBox{$id}", 'class' => 'OrderedListForm'));
        $output.= $this->Form->hidden('id', array('value' => $id));
        $output.= $this->Form->select($field, $positionsList, $position, array('onChange' => 'this.form.submit();'), false);
        //$output.= $this->Form->hidden('field_name', array('value' => $field));
        //$output.= $this->Form->hidden('previous_url', array('value' => $_SERVER['REQUEST_URI']));
        $output.= $this->Form->hidden('previous_url', array('value' => '/'.$this->params['url']['url']));
        $output.= $this->Form->end(null);

        return $this->output($output);
    }    

}