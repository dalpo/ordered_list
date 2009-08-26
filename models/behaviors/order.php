<?php
/**
 *  Order Behaviour
 *  @author Andrea Dal Ponte (dalpo85@gmail.com)
 *  @link none
 *  @filesource none
 *  @version 0.2
 *  @modifiedby      $LastChangedBy: dalpo85@gmail.com
 *  @lastmodified    $Date:2009/08/06$
 *
 *  Version Details
 *
 *
 *
 */
class OrderBehavior extends ModelBehavior {

    protected $_defaultSettings = array(
              'field' => 'position',
              'scope' => null
    );

    protected $_entity = null;
    protected $_scope = null;

    function setup(&$model, $config = array()) {
        $settings = am($this->_defaultSettings, $config);
        $this->settings[$model->alias] = $settings;
        $model->_orderedScope = $this->_scope = $settings['scope'];

    }

    /**
     * Callbacks
     */

    function afterSave(&$model, $created) {
        parent::afterSave($model, $created);
        if($created) {
            if($this->_scope) {
                $position = (int)$model->find(
                    'count',
                    array('conditions' => array($model->data[$model->alias].'.'.$this->_scope => $model->data[$model->alias][$this->_scope]))
                );
            } else {
                $position = (int)$model->find('count');
            }
            
            $saveOptions = array(
                'validate'  => false,
                'fieldList' => array($this->settings[$model->alias]['field']),
                'callbacks' => false
            );            
            $id = $model->id;
            $data = array($model->alias => array($model->primaryKey => $id, $this->settings[$model->alias]['field'] => $position));
            $model->save($data, $saveOptions);            
        }
        $model->recursive = -1;
        $model->read();
        return true;
    }

    function beforeDelete(&$model) {
        parent::beforeDelete($model);
        $model->recursive = -1;
        $this->_entity = $model->read();
        return true;
    }

    function afterDelete(&$model) {
        parent::afterDelete($model);
        if($this->_entity && $this->_entity[$model->alias][$model->primaryKey] == $model->id) {
            $conditions = array(
                "{$model->alias}.{$this->settings[$model->alias]['field']} >" => $this->_entity[$model->alias][$this->settings[$model->alias]['field']]
            );
            if($this->_scope) {
                $conditions["{$model->alias}.{$this->_scope}"] = $this->_entity[$model->alias][$this->_scope];
            }
            $model->updateAll(
                array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} - 1"),
                $conditions
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
        $position = $this->_scope ? array() : 0;
        $fields = array(
            "{$model->alias}.{$model->primaryKey}",
            "{$model->alias}.{$this->settings[$model->alias]['field']}"
        );
        if($this->_scope) {
            $fields[] = "{$model->alias}.{$this->_scope}";
        }
        $results = $model->find(
            'all', array(
                'fields'=> $fields,
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
            if($this->_scope) {
                if(isset($position[$record[$model->alias][$this->_scope]])) {
                    $updateValue = ++$position[$record[$model->alias][$this->_scope]];
                } else {
                    //SIAMO SICURI?? $updateValue NON DOVREBBE ESSERE 1 IN QUESTO CASO??
                    $updateValue = $position[$record[$model->alias][$this->_scope]] = 1;
                }
            } else {
                $updateValue = ++$position;
            }
            $data = array($model->alias => array($model->primaryKey => $id, $this->settings[$model->alias]['field'] => $updateValue));
            $model->save($data, $saveOptions);
        }
    }

    function reorder(&$model) {
        $this->reorderByField($model);
    }

    function moveTo(&$model, $position = 1) {
        if($model->id) {
            $this->_entity = $model->read();
            $oldPosition = $this->_entity[$model->alias][$this->settings[$model->alias]['field']];            
            if($position > $oldPosition) {
                $conditions = array(
                    "{$model->alias}.{$this->settings[$model->alias]['field']} >" => $oldPosition,
                    "{$model->alias}.{$this->settings[$model->alias]['field']} <=" => $position
                );
                if($this->_scope) {
                    $conditions["{$model->alias}.{$this->_scope}"] = $this->_entity[$model->alias][$this->_scope];
                }
                $model->updateAll(
                    array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} - 1"),
                    $conditions
                );
            }
            if($position < $oldPosition) {
                $conditions = array(
                    "{$model->alias}.{$this->settings[$model->alias]['field']} >=" => $position,
                    "{$model->alias}.{$this->settings[$model->alias]['field']} <" => $oldPosition
                );
                if($this->_scope) {
                    $conditions["{$model->alias}.{$this->_scope}"] = $this->_entity[$model->alias][$this->_scope];
                }
                $model->updateAll(
                    array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} + 1"),
                    $conditions
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
            $this->moveTo($model, 1);
        }
    }

    function moveBottom($model) {
        if($model->id) {
            $conditions = array();
            if($this->_scope) {
                $entity = $this->read();
                $conditions["{$model->alias}.{$this->_scope}"] = $entity[$model->alias][$this->_scope];
            }
            $this->moveTo($model, $model->find('count', array('conditions' => $conditions)));
        }
    }


    function positionsList(&$model) {
        if($this->_scope) {
            $order = array(
                "{$model->alias}.{$this->_scope}" => 'ASC',
                "{$model->alias}.{$this->settings[$model->alias]['field']}" => 'ASC'
            );
            
            return $model->find(
                'list',
                array(
                    'fields' => array(
                        "{$model->alias}.{$this->settings[$model->alias]['field']}",
                        "{$model->alias}.{$this->settings[$model->alias]['field']}",
                        "{$model->alias}.{$this->_scope}"
                    ),
                    'order' => $order
                )
            );
        } else {
            $order = array("{$model->alias}.{$this->settings[$model->alias]['field']}" => 'ASC');
            return $model->find(
                'list',
                array(
                    'fields' => array(
                        "{$model->alias}.{$this->settings[$model->alias]['field']}",
                        "{$model->alias}.{$this->settings[$model->alias]['field']}"
                    ),
                    'order' => $order
                )
            );
        }
    }


}

?>
