<?php
class OrderedListAppController extends AppController {

    public      $helpers = array('OrderedList.Order');
    public      $OrderedModel = null;
    protected   $positionsListAutoload = true;

    function beforeFilter() {
        parent::beforeFilter();
        $this->OrderedModel =& $this->_getOrderedModel();
    }

    function beforeRender() {
        parent::beforeRender();
        $this->_positionsList();
    }
    
    function order() {
        $id = (int)$this->data[$model]['id'];
        $position = (int)$this->data[$model]['position'];

        $this->OrderedModel->id = $id;
        $this->OrderedModel->moveTo($position);
        if(isset($this->data[$model]['previous_url'])) {
            $this->redirect($this->data[$model]['previous_url']);
        } else {
            $this->redirect(array('action' => 'index'));
        }
    }

    /**
     * Reorder all record by id ASC
     */
    function reorder() {
        $this->OrderedModel->reorder();
        $this->redirect(array('action' => 'index'));
    }

    function admin_reorder() {
        $this->OrderedModel->reorder();
        $this->redirect(array('action' => 'index'));
    }

    /**
     * @fixme this is a bad way...
     */
    protected function _getOrderedModel() {
        $model = $this->uses[0];
        return $this->{$model};
    }

    protected function _positionsList() {
        if(!$this->OrderedModel->_orderedScope && $this->positionsListAutoload) {
            $this->set('positionsList', $this->OrderedModel->positionsList());
        }
    }

}
?>