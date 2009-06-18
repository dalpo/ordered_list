<?php
class OrderedListAppController extends AppController {

    public $helpers = array('OrderedList.Order');

    function beforeRender() {
        parent::beforeRender();
        $this->_positionsList();
    }
    
    function order() {
        //Do I get the first model?
        $model = $this->uses[0];
        $id = (int)$this->data[$model]['id'];
        $position = (int)$this->data[$model]['position'];

        $this->{$model}->id = $id;
        $this->{$model}->moveTo($position);
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
        $model = $this->uses[0];
        $this->{$model}->reorder();
        $this->redirect(array('action' => 'index'));
    }

    function admin_reorder() {
        $model = $this->uses[0];
        $this->{$model}->reorder();
        $this->redirect(array('action' => 'index'));
    }

    protected function _positionsList() {
        $model = $this->uses[0];
        $this->set('positionsList', $this->{$model}->positionsList());
    }

}
?>