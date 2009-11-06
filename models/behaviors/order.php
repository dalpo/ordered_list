<?php
/**
 *  Order Behaviour
 *  @author Andrea Dal Ponte (dalpo85@gmail.com)
 *  @link none
 *  @filesource order.php
 *  @version 0.3.5
 *  @modifiedby      $LastChangedBy: dalpo85@gmail.com
 *  @lastmodified    $Date:2009/11/06$
 *
 *  Version Details
 *
 *  - Scope Support
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
    }

    /**
     * Callbacks
     */

    function beforeSave(&$model) {
        parent::beforeSave($model);
        if($model->id) {
            $model->recursive = -1;
            $this->_entity = $model->find('first', array('conditions' => array("{$model->alias}.{$model->primaryKey}" => $model->id)));
        }
        return true;
    }

    function afterSave(&$model, $created) {
        parent::afterSave($model, $created);
        $currentScope = $this->getScope($model);
        if($created) {
            if($currentScope) {
                $position = (int)$model->find(
                    'count',
                    array('conditions' => array($model->alias.'.'.$currentScope => $model->data[$model->alias][$currentScope]))
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
        } else {
            //If there is a different scope value:
            if($currentScope && ($this->_entity[$model->alias][$currentScope] != $model->data[$model->alias][$currentScope])) {
                //new position
                $position = (int)$model->find(
                    'count',
                    array('conditions' => array($model->alias.'.'.$currentScope => $model->data[$model->alias][$currentScope]))
                );

                $model->updateAll(
                    array("{$model->alias}.{$this->settings[$model->alias]['field']}" => "{$model->alias}.{$this->settings[$model->alias]['field']} - 1"),
                    array(
                        "{$model->alias}.{$currentScope}" => $this->_entity[$model->alias][$currentScope],
                        "{$model->alias}.{$this->settings[$model->alias]['field']} >" => $this->_entity[$model->alias][$this->settings[$model->alias]['field']]
                    )
                );

                $saveOptions = array(
                    'validate'  => false,
                    'fieldList' => array($this->settings[$model->alias]['field']),
                    'callbacks' => false
                );

                $id = $model->id;
                $data = array($model->alias => array($model->primaryKey => $id, $this->settings[$model->alias]['field'] => $position));
                $model->save($data, $saveOptions);
            }
        }
        $this->_entity = null;
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
        $currentScope = $this->getScope($model);
        if($this->_entity && $this->_entity[$model->alias][$model->primaryKey] == $model->id) {
            $conditions = array(
                "{$model->alias}.{$this->settings[$model->alias]['field']} >" => $this->_entity[$model->alias][$this->settings[$model->alias]['field']]
            );
            if($currentScope) {
                $conditions["{$model->alias}.{$currentScope}"] = $this->_entity[$model->alias][$currentScope];
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
        $currentScope = $this->getScope($model);
        $position = $currentScope ? array() : 0;
        $fields = array(
            "{$model->alias}.{$model->primaryKey}",
            "{$model->alias}.{$this->settings[$model->alias]['field']}"
        );
        if($currentScope) {
            $fields[] = "{$model->alias}.{$currentScope}";
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
            if($currentScope) {
                if(isset($position[$record[$model->alias][$currentScope]])) {
                    $updateValue = ++$position[$record[$model->alias][$currentScope]];
                } else {
                    $updateValue = $position[$record[$model->alias][$currentScope]] = 1;
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
            $currentScope = $this->getScope($model);
            $this->_entity = $model->read();
            $oldPosition = $this->_entity[$model->alias][$this->settings[$model->alias]['field']];
            if($position > $oldPosition) {
                $conditions = array(
                    "{$model->alias}.{$this->settings[$model->alias]['field']} >" => $oldPosition,
                    "{$model->alias}.{$this->settings[$model->alias]['field']} <=" => $position
                );
                if($currentScope) {
                    $conditions["{$model->alias}.{$currentScope}"] = $this->_entity[$model->alias][$currentScope];
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
                if($currentScope) {
                    $conditions["{$model->alias}.{$currentScope}"] = $this->_entity[$model->alias][$currentScope];
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
        $currentScope = $this->getScope($model);
        if($model->id) {
            $conditions = array();
            if($currentScope) {
                $entity = $this->read();
                $conditions["{$model->alias}.{$currentScope}"] = $entity[$model->alias][$currentScope];
            }
            $this->moveTo($model, $model->find('count', array('conditions' => $conditions)));
        }
    }

    function getScope($model) {
      return $this->settings[$model->alias]['scope'];
    }


    function positionsList(&$model) {
        $currentScope = $this->getScope($model);
        if($currentScope) {
            $order = array(
                "{$model->alias}.{$currentScope}" => 'ASC',
                "{$model->alias}.{$this->settings[$model->alias]['field']}" => 'ASC'
            );

            return $model->find(
              'list',
              array(
                'fields' => array(
                  "{$model->alias}.{$this->settings[$model->alias]['field']}",
                  "{$model->alias}.{$this->settings[$model->alias]['field']}",
                  "{$model->alias}.{$currentScope}"
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
