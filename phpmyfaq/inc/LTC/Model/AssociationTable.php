<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: AssociationTable.php,v 1.1 2008-04-28 21:14:20 lars Exp $
 * @copyright  Copyright 2008 Lars Tiedemann
 * @since      07.04.2008
 */

/**
 * LTC_Model_AssociationTable
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_AssociationTable
    extends LTC_Model_Abstract
{
    
    /**
     * The source model
     *
     * @var LTC_Model_Interface
     */
    protected $model = null;
    
    /**
     * The target model
     *
     * @var LTC_Model_Interface
     */
    protected $associatedModel = null;
    
    
    /**
     * Creates a new LTC_AssociationTable instance
     *
     * @param LTC_Model_Interface $model
     * @param LTC_Model_Interface $associatedModel
     */
    public function __construct(LTC_Model_Interface $model, LTC_Model_Interface $associatedModel) 
    {
        $this->model = $model;
        $this->associatedModel = $associatedModel;
        $this->name = sprintf('%s_%s', $this->model->getName(), $this->associatedModel->getName());
        $key1 = $this->model->getPrimaryKey();
        $key2 = $this->associatedModel->getPrimaryKey();
        $this->fields[$key1] = $this->model->getFieldProperties($key1);
        $this->fields[$key2] = $this->associatedModel->getFieldProperties($key2);
    }
    
    /**
     * Initializes the model object.
     * 
     * @return void
     */
    public function init()
    {}
    
}
