<?php
/**
 * This file is part of OpenClinic
 *
 * Copyright (c) 2002-2005 jact
 * Licensed under the GNU GPL. For full terms see the file LICENSE.
 *
 * $Id: Access_Page_Query.php,v 1.2 2005/07/21 15:59:46 jact Exp $
 */

/**
 * Access_Page_Query.php
 *
 * Contains the class Access_Page_Query
 *
 * Author: jact <jachavar@gmail.com>
 */

require_once("../classes/Page_Query.php");

/**
 * Access_Page_Query data access component for application users accesses
 *
 * @author jact <jachavar@gmail.com>
 * @access public
 * @since 0.3
 *
 * Methods:
 *  void Access_Page_Query(void)
 *  mixed select(int $year = 0, int $month = 0, int $day = 0, int $hour = 0)
 *  bool searchUser(int $idUser, int $page, int $limitFrom = 0)
 *  mixed fetch(void)
 *  bool insert(User $user)
 */
class Access_Page_Query extends Page_Query
{
  /**
   * void Access_Page_Query(void)
   *
   * Constructor function
   *
   * @return void
   * @access public
   */
  function Access_Page_Query()
  {
    $this->_table = "access_log_tbl";
  }

  /**
   * mixed select(int $year = 0, int $month = 0, int $day = 0, int $hour = 0)
   *
   * Executes a query
   *
   * @param int $year (optional)
   * @param int $month (optional)
   * @param int $day (optional)
   * @param int $hour (optional)
   * @return mixed if error occurs returns false, else number of rows in the result
   * @access public
   * @since 0.4
   */
  function select($year = 0, $month = 0, $day = 0, $hour = 0)
  {
    $sql = "SELECT login,access_date,id_profile";
    $sql .= " FROM " . $this->_table;
    $sql .= " WHERE 1";
    if ($year != "")
    {
      $sql .= " AND YEAR(access_date)=" . intval($year);
    }
    if ($month != "")
    {
      $sql .= " AND MONTH(access_date)=" . intval($month);
    }
    if ($day != "")
    {
      $sql .= " AND DATE_FORMAT(access_date, '%d')=" . intval($day);
    }
    if ($hour != "")
    {
      $sql .= " AND DATE_FORMAT(access_date, '%H')=" . intval($hour);
    }
    $sql .= " ORDER BY access_date DESC";

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error accessing user access information.";
      return false;
    }

    return $this->numRows();
  }

  /**
   * bool searchUser(int $idUser, int $page, int $limitFrom = 0)
   *
   * Executes a query
   *
   * @param int $idUser
   * @param int $page What page should be returned if results are more than one page
   * @param int $limitFrom (optional) maximum number of results
   * @return boolean returns false, if error occurs
   * @access public
   * @since 0.7
   */
  function searchUser($idUser, $page, $limitFrom = 0)
  {
    // reset stats
    $this->_rowNumber = 0;
    $this->_currentRow = 0;
    $this->_currentPage = ($page > 1) ? intval($page) : 1;
    $this->_rowCount = 0;
    $this->_pageCount = 0;

    $sql = " FROM " . $this->_table;
    $sql .= " WHERE id_user=" . intval($idUser);

    $sqlCount = "SELECT COUNT(*) AS row_count" . $sql;

    $sql = "SELECT login,access_date,id_profile" . $sql;
    $sql .= " ORDER BY access_date DESC";

    // setting limit so we can page through the results
    $offset = ($this->_currentPage - 1) * intval($this->_itemsPerPage);
    if ($offset >= $limitFrom && $limitFrom > 0)
    {
      $offset = 0;
    }
    $limitTo = intval($this->_itemsPerPage);
    if ($limitTo > 0)
    {
      $sql .= " LIMIT " . $offset . "," . $limitTo . ";";
    }

    //Error::debug($limitFrom, "limitFrom"); // debug
    //Error::debug($offset, "offset"); // debug
    //Error::debug($sql, "sql"); // debug

    // Running row count sql statement
    $countResult = $this->exec($sqlCount);
    if ($countResult == false)
    {
      $this->_error = "Error counting user access results.";
      return false;
    }

    // Calculate stats based on row count
    $array = $this->fetchRow();
    $this->_rowCount = $array["row_count"];
    if ($limitFrom > 0 && $limitFrom < $this->_rowCount)
    {
      $this->_rowCount = $limitFrom;
    }
    $this->_pageCount = (intval($this->_itemsPerPage) > 0) ? ceil($this->_rowCount / $this->_itemsPerPage) : 1;

    // Running search sql statement
    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error searching user access information.";
      return false;
    }

    return $result;
  }

  /**
   * mixed fetch(void)
   *
   * Fetches a row from the query result and populates an array object.
   *
   * @return array returns access log or false if no more logs to fetch
   * @access public
   * @since 0.4
   */
  function fetch()
  {
    $array = $this->fetchRow();
    if ($array == false)
    {
      return false;
    }

    // increment rowNumber
    $this->_rowNumber = $this->_rowNumber + 1;
    $this->_currentRow = $this->_rowNumber + (($this->_currentPage - 1) * $this->_itemsPerPage);

    $access = array();
    $access["login"] = urldecode($array["login"]);
    $access["access_date"] = urldecode($array["access_date"]);
    $access["id_profile"] = intval($array["id_profile"]);

    return $access;
  }

  /**
   * bool insert(User $user)
   *
   * Inserts a new application user access into the database.
   *
   * @param User $user application user data
   * @return boolean returns false, if error occurs
   * @access public
   */
  function insert($user)
  {
    $sql = "INSERT INTO " . $this->_table;
    $sql .= " (id_user, login, access_date, id_profile) VALUES (";
    $sql .= $user->getIdUser() . ", ";
    $sql .= "'" . urlencode($user->getLogin()) . "', ";
    $sql .= "NOW(), ";
    $sql .= $user->getIdProfile() . ");";

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error inserting new user access information.";
    }

    return $result;
  }
} // end class
?>