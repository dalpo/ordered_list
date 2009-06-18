<?php
/**
 *  Order Behaviour
 *  @author Andrea Dal Ponte (dalpo85@gmail.com)
 *  @link none
 *  @filesource none
 *  @version 0.1
 *  @modifiedby      $LastChangedBy: dalpo85@gmail.com
 *  @lastmodified    $Date:2009/06/18$
 *
 *  Version Details
 *
 *
 *
 */
class OrderBehavior extends ModelBehavior {

    protected $_defaultSettings = array(
              'field' => 'position'
    );

    protected $_entity = null;

    function setup(&$model, $config = array()) {
        $settings = am($this->_defaultSettings, $config);
        $this->settings[$model->alias] = $settings;
    }

    /**
     * Callbacks
     */

    function afterSave(&$model, $created) {
        parent::afterSave(&$model, $created);

        if($created) {            
            $position = $model->find('count');

            $saveOptions = array(
                'validate'  => false,
                'fieldList' => array($this->settings[$model->alias]['field']),
                'callbacks' => false
            );            
            $id = $model->id;
            $data = array($model->alias => array($model->primaryKey => $id, $this->settings[$model->alias]['field'] => $position));
            $model->save($data, $saveOptions);            
        }

        $model->read();
        return true;
    }

    function beforeDelete(&$model) {
        parent::beforeDelete(&$model);
        $model->recursive = -1;
        $this->_entity = $model->read();
        return true;
    }

    function afterDelete(&$model) {
        parent::beforeDelete(&$model);
        if($this->_entity && $this->_entity[$model->alias][$model->primaryKey] == $model->id) {
            $model->updateAll(
                array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} - 1"),
                array("{$model->alias}.{$this->settings[$model->alias]['field']} >" => $this->_entity[$model->alias][$this->settings[$model->alias]['field']])
            );
            $this->_entity = null;
        } else {
            $this->log("WARNING: OrderedListBehavior >> something is wrong!");
        }
        return true;
    }

    /**
     * Behavior methods
     */

    function reorderByField(&$model, $field = 'id', $direction = 'ASC') {
        $position = 0;
        $results = $model->find(
            'all', array(
                'fields'=> array(
                    "{$model->alias}.{$model->primaryKey}",
                    "{$model->alias}.{$this->settings[$model->alias]['field']}"
                ),
                'order' => array($field => $direction)
            )
        );
        $saveOptions = array(
            'validate'  => false,
            'fieldList' => array($this->settings[$model->alias]['field']),
            'callbacks' => false
        );
        foreach ($results as $record) {
            $id = $record[$model->alias][$model->primaryKey];
            $data = array($model->alias => array($model->primaryKey => $id, $this->settings[$model->alias]['field'] => ++$position));
            $model->save($data, $saveOptions);
        }
    }


    function reorder(&$model) {
        $this->reorderByField(&$model);
    }


    function moveTo(&$model, $position = 1) {
        if($model->id) {
            $this->_entity = $model->read();
            $oldPosition = $this->_entity[$model->alias][$this->settings[$model->alias]['field']];
            if($position > $oldPosition) {
                $model->updateAll(
                    array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} - 1"),
                    array(
                        "{$model->alias}.{$this->settings[$model->alias]['field']} >" => $oldPosition,
                        "{$model->alias}.{$this->settings[$model->alias]['field']} <=" => $position
                    )
                );
            }
            if($position < $oldPosition) {
                $model->updateAll(
                    array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} + 1"),
                    array(
                        "{$model->alias}.{$this->settings[$model->alias]['field']} >=" => $position,
                        "{$model->alias}.{$this->settings[$model->alias]['field']} <" => $oldPosition
                    )
                );
            }
            if($position != $oldPosition) {
                $saveOptions = array(
                    'validate'  => false,
                    'fieldList' => array($this->settings[$model->alias]['field']),
                    'callbacks' => false
                );

                $data = array($model->alias => array($model->primaryKey => $this->_entity[$model->alias][$model->primaryKey], $this->settings[$model->alias]['field'] => $position));
                $model->save($data, $saveOptions);
            }
        }
    }

    function moveTop(&$model) {
        if($model->id) {
            $this->moveTo(&$model, 1);
        }
    }

    function moveBottom(&$model) {
        if($model->id) {
            $this->moveTo(&$model, $model->find('count'));
        }
    }

    function positionsList(&$model) {
        return $model->find(
            'list',
            array(
                'fields' => array($this->settings[$model->alias]['field'], $this->settings[$model->alias]['field']),
                'order' => array($this->settings[$model->alias]['field'] => 'ASC')
            )
        );
    }


}

?>