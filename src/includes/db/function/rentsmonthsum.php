<?php
/*************************************************************************
 *                                                                       *
 * Copyright (C) 2010   Olivier JULLIEN - PBRAIDERS.COM                  *
 * Tous droits réservés - All rights reserved                            *
 *                                                                       *
 *************************************************************************
 *                                                                       *
 * Except if expressly provided in a dedicated License Agreement,you     *
 * are not authorized to:                                                *
 *                                                                       *
 * 1. Use,copy,modify or transfer this software component,module or      *
 * product,including any accompanying electronic or paper documentation  *
 * (together,the "Software").                                            *
 *                                                                       *
 * 2. Remove any product identification,copyright,proprietary notices    *
 * or labels from the Software.                                          *
 *                                                                       *
 * 3. Modify,reverse engineer,decompile,disassemble or otherwise         *
 * attempt to reconstruct or discover the source code,or any parts of    *
 * it,from the binaries of the Software.                                 *
 *                                                                       *
 * 4. Create derivative works based on the Software (e.g. incorporating  *
 * the Software in another software or commercial product or service     *
 * without a proper license).                                            *
 *                                                                       *
 * By installing or using the "Software",you confirm your acceptance     *
 * of the hereabove terms and conditions.                                *
 *                                                                       *
 * file encoding: UTF-8                                                  *
 *                                                                       *
 *************************************************************************/
if( !defined('PBR_VERSION') || !defined('PBR_DB_LOADED') )
    die('-1');

/**
  * function: RentsMonthCount
  * description: Get the sum of real, planned and canceled rents by month
  * parameters: STRING|sLogin    - login identifier
  *             STRING|sSession  - session identifier
  *             STRING|sInet     - concatenation of IP and USER_AGENT
  *              CDate|pDate     - current date
  *            COption|pInterval - interval
  * return: BOOLEAN - FALSE if an exception occures
  *         or
  *         INTEGER - -1 when a private error occures
  *                   -2 when an authentication error occures.
  *                   -3 when an access denied error occures.
  *                   -4 when a duplicate error occures.
  *         or
  *         ARRAY of :   'info' => array( 'min' => <value>, 'max' => <value> )
  *                    'values' => array( years => array( months => array(real, planned, canceled) ) )
  * author: Olivier JULLIEN - 2010-06-15
  */
function RentsMonthSum( $sLogin, $sSession, $sInet, CDate $pDate, COption $pInterval)
{
    /** Initialize
     *************/
    $iReturn = -1;
    $sMessage = '';
    $sErrorTitle = __FUNCTION__ .'('.$sLogin.','.$sSession.',[obfuscated],'.$pInterval->GetValue().')';

    /** Request
     **********/
    if( (CDBLayer::GetInstance()->IsOpen()===TRUE)
     && IsScalarNotEmpty(PBR_DB_DBN)
     && IsStringNotEmpty($sLogin)
     && IsStringNotEmpty($sSession)
     && IsStringNotEmpty($sInet) )
    {

        // Build interval
        $iYear = $pDate->GetCurrentYear() - $pInterval->GetValue();

        try
        {
            // Prepare
            $sSQL = 'SELECT r.`year` AS "year", r.`month` AS "month", SUM(rent_real) AS "real", SUM(rent_planned) AS "planned", SUM(rent_canceled) AS "canceled" FROM `'.PBR_DB_DBN.'`.`reservation` AS r WHERE r.`year`>=:iYear GROUP BY r.`year`,r.`month`';
            $pPDOStatement = CDBLayer::GetInstance()->GetDriver()->prepare($sSQL);
            // Bind
            $pPDOStatement->bindValue(':iYear',$iYear,PDO::PARAM_INT);
            // Execute
            $pPDOStatement->execute();
            // Fetch
            $iReturn = array();
            $iReturn['info'] = array('min'=>0, 'max'=>0);
            $iReturn['values'] = array();
            $tTemp = array();
            $iIndex = 0;
            while( $tRow = $pPDOStatement->fetch(PDO::FETCH_ASSOC) )
            {
                // Convert values
                $iYears  = (integer) $tRow['year'];
                $iMonths = (integer) $tRow['month'];
                // First record for this year
                if( !isset( $tTemp[$iYears] ) )
                    $tTemp[$iYears] = array();
                // Add value
                $tTemp[$iYears][$iMonths] = array( 'real' => (integer)$tRow['real'], 'planned' => (integer)$tRow['planned'], 'canceled' => (integer)$tRow['canceled'] );
                // Compute min and max values
                $iSumMin = (integer) min( (integer)$tRow['real'], (integer)$tRow['planned'], (integer)$tRow['canceled'] );
                $iSumMax = (integer) max( (integer)$tRow['real'], (integer)$tRow['planned'], (integer)$tRow['canceled'] );
                if( $iIndex===0 )
                {
                    $iIndex=1;
                    $iReturn['info']['min'] = $iSumMin;
                    $iReturn['info']['max'] = $iSumMax;
                }
                else
                {
                    if( $iSumMin < $iReturn['info']['min'] )
                        $iReturn['info']['min'] = $iSumMin;
                    if( $iSumMax > $iReturn['info']['max'] )
                        $iReturn['info']['max'] = $iSumMax;
                }//if( $iIndex===0 )

            }//while
            $iReturn['values'] = $tTemp;
        }
        catch(PDOException $e)
        {
            $iReturn = FALSE;
            $sMessage = $e->getMessage();
        }//try

        // Free resource
        $pPDOStatement = NULL;

    }//if(...

    // Error
    if( is_scalar($iReturn) )
    {
        ErrorDBLog( $sLogin, $sErrorTitle, $sMessage, $iReturn, TRUE);
    }//if( is_scalar($iReturn) )

    return $iReturn;
}
