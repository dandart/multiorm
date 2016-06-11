<?php
/**
 * This file is part of MultiORM.
 *
 * MultiORM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MultiORM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with MultiORM. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    MultiORM
 * @author     Dan Dart
 * @copyright  2016 MultiORM
 * @license    http://www.gnu.org/licenses/agpl-3.0.html GNU AGPL 3.0
 * @version    git
 * @link       https://github.com/dandart/multiorm
**/
namespace MultiORM\Dao\Mongo;

use MultiORM\Dao\DaoInterface;
use MultiORM\Model\Field\Hash;
use MultiORM\Model\Field\Field;
use MultiORM\Model\Field\Readonly;
use MultiORM\Model\Field\FieldId;
use MultiORM\Model\Field\Collection;

abstract class MongoAbstract implements DaoInterface
{
    const FIELD_Id = '_id';

    protected $_strCollection = null; // You must set this!
    protected $_strDatabase = null;

    private $_mongoCollection;
    private $_mongoConnection;

    public function __construct()
    {
    }

    abstract protected function _getCollectionName();

    protected function _save(Hash $hash)
    {
        $collFields = $hash->getFields($this);

        if (!isset($collFields[self::FIELD_Id])) {
            throw new Exception('Cannot save hashes without _id');
        }

        if (is_null($collFields[self::FIELD_Id]->getValue(null))) {
            throw new Exception('Id is null');
        }

        $arrCriteria = array(self::FIELD_Id => $collFields[self::FIELD_Id]->getValue(null));
        $arrUpdate = array('$set' => $this->_getUpdateArray($collFields));
        if(empty($arrUpdate)) {
            // We're doomed if we let this go through
            // Nothing!
            return;
        }

        if(isset($arrUpdate['$set'][self::FIELD_Id])) {
            unset($arrUpdate['$set'][self::FIELD_Id]);
        }

        $this->_getCollection()->updateArray($arrCriteria, $arrUpdate);
    }

    protected function _delete(Hash $hash)
    {
        $arrCriteria = array(self::FIELD_Id => $hash->getId());
        $this->_getCollection()->removeArray($arrCriteria);
    }

    private function _textToSafe($strText)
    {
        if('UTF-8' != mb_detect_encoding($strText)) {
            $strText = mb_convert_encoding($strText, 'UTF-8');
        }
        return $strText;
    }

    private function _getUpdateArray(Array $collFields, $strPrefix = '')
    {
        $arrUpdate = array();
        foreach($collFields as $strFieldName => $objField) {
            if($objField->bIsDirty()) {
                $strClass = get_class($objField);
                switch($strClass) {
                    case Field::class:
                        $arrUpdate[$strPrefix.$strFieldName] = $this->_textToSafe($objField->getValue(null));
                        break;
                    case ReadOnly::class:
                    case FieldId::class:
                        // Ids do not update
                        break;
                    case Collection::class:
                        foreach($objField as $hash) {
                            foreach(
                                $this->_getUpdateArray(
                                    $hash->getFields($this),
                                    $strFieldName.'.'.$hash->getId().'.'
                                ) as $strField => $mixedValue) {
                                $arrUpdate[$strField] = $mixedValue;
                            }
                        }
                        break;
                    default:
                        throw new Exception('Not Implemented class '.$strClass);
                }
            }
        }
        return $arrUpdate;
    }

    public function setMongoCollection(Mongo_Collection $mongoCollection)
    {
        $this->_mongoCollection = $mongoCollection;
    }
    protected function _getCollection()
    {
        if(is_null($this->_mongoCollection))
            $this->_mongoCollection = new Mongo_Collection($this->_strDatabase, $this->_getCollectionName());
        return $this->_mongoCollection;
    }

    public function setMongoConnection(Mongo_Connection $mongoConnection)
    {
        $this->_mongoConnection = $mongoConnection;
    }
    protected function _getConnection()
    {
        if(is_null($this->_mongoConnection))
            $this->_mongoConnection = new Mongo_Connection();
        return $this->_mongoConnection;
    }

    protected function _runDistinct($strCollection, $strKey, Array $arrCommand)
    {
        return $this->_getConnection()->distinct($this->_strDatabase, $strCollection, $strKey, $arrCommand);
    }

   // abstract protected function _convertToModel(Array $arrMongo);

    // You must implement delete()
    // You must implement save()
}
