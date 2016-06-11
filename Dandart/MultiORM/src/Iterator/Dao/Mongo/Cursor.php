<?php
/**
 * This file is part of Project Chaplin.
 *
 * Project Chaplin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Project Chaplin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Project Chaplin. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    Project Chaplin
 * @author     Tim Langley
 * @author     Dan Dart
 * @copyright  2012-2013 Project Chaplin
 * @license    http://www.gnu.org/licenses/agpl-3.0.html GNU AGPL 3.0
 * @version    git
 * @link       https://github.com/dandart/projectchaplin
**/
class Chaplin_Iterator_Dao_Mongo_Cursor implements Chaplin_Iterator_Interface
{
    private $_cursor;
    private $_daoInterface;
    private $_bEmpty        = false;

    private $_intOffset     = 0; 
    private $_intStartRow   = 0; 
    private $_intReturnRows;

    public function __construct(Mongo_Cursor $cursor, Chaplin_Dao_Mongo_Abstract $daoInterface)
    {
        $this->_cursor      = $cursor;
        $this->_daoInterface = $daoInterface;
    }
    public function isEmpty()
    {
        if (0 == count($this->_cursor)) {
            $this->_bEmpty = true;
        }
        return $this->_bEmpty;
    }
    public function count()
    {
        return count($this->_cursor);
    }
    public function current()
    {
        if (is_null($this->_cursor->current())) {
            $this->_cursor->next();
        }
        $arrCurrentItem = $this->_cursor->current();
        if (is_null($arrCurrentItem)) {
            throw new Chaplin_Iterator_Exception_NonexistentModel(get_class($this->_daoInterface));
        }
        return $this->_daoInterface->convertToModel($arrCurrentItem);
    }
    function key() 
    {
        return $this->_cursor->key();
    }
    function next() 
    {
        return $this->_cursor->next();
    }
    function rewind() 
    {
        return $this->_cursor->rewind();
    }
    function valid()
    {
        return $this->_cursor->valid();
    }
    //Implements ArrayAccess
    public function offsetSet($offset, $value) 
    {
        throw new Chaplin_Exception_NotImplemented();
    } 
    public function offsetExists($offset)
    {
        throw new Chaplin_Exception_NotImplemented();
    }
    public function offsetUnset($offset)
    {
        throw new Chaplin_Exception_NotImplemented();
    } 
    public function offsetGet($offset)
    {
        throw new Chaplin_Exception_NotImplemented();
    }

    //Chaplin_Iterator_Interface
    public function countAll()
    {
        return $this->count();
    }
    public function countDisplay()
    {
        return $this->_cursor->count(true);
    }
    /**
     *  Limits the number of rows to be returned in the cursor
     *  @param:     $intNoRows  = number of rows to return
     *  @return:    $this (this is a fluent interface)
     **/
    public function limit($intNoRows)
    {
        $this->_cursor = $this->_cursor->limit($intNoRows);
        return $this;
    }
    /**
     *  Skips the first  $intNoRows
     *  @param:     $intNoRows  = number of rows to skip
     *  @return:    $this (this is a fluent interface)
     **/
    public function skip($intNoRows)
    {
        $this->_cursor = $this->_cursor->skip($intNoRows);
        return $this;
    }
    /**
     *  Sorts the cursor 
     *  @param:     $arrColumns     Associative array of Key => value (1 = ASC, -1 = DESC)
     *  @return:    $this (this is a fluent interface)
     **/
    public function sort(Array $arrColumns = array())
    {
        if (!empty($arrColumns))
            $this->_cursor->sort($arrColumns);
        return $this;
    }

    //Implements SeekableIterator
    public function seek($strPosition)
    {
        foreach ($this->_cursor as $key => $arrCurrentItem) {
            if ($strPosition == $key) {
                return $this->_daoInterface->convertToModel($arrCurrentItem);
            }
        }
        throw new OutOfBoundsException(__CLASS__);
    }
}
