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
  * function: ContactDel
  * description: Delete a contact and linked reservations.
  * parameters: STRING|sLogin   - login identifier
  *             STRING|sSession - session identifier
  *             STRING|sInet    - concatenation of IP and USER_AGENT
  *           CContact|pContact - instance of CContact. Identifier should be correctly filled.
  * return: BOOLEAN - FALSE if an exception occures
  *         or
  *         INTEGER - >=0 number of records deleted.
  *                    -1 when a private error occures.
  *                    -2 when an authentication error occures.
  *                    -3 when an access denied error occures.
  *                    -4 when a duplicate error occures.
  * author: Olivier JULLIEN - 2010-02-04
  * update: Olivier JULLIEN - 2010-05-24 - use ErrorDBLog instead of CErrorList::AddDB(...) and CDBLayer::GetInstance()->LogError(...)
  * update: Olivier JULLIEN - 2010-06-15 - improvement
  */
function ContactDel( $sLogin, $sSession, $sInet, CContact $pContact)
{
    /** Initialize
     *************/
    $iReturn = -1;
    $sMessage = '';
    $sErrorTitle =__FUNCTION__ .'('.$sLogin.','.$sSession.',[obfuscated],'.$pContact->GetIdentifier().')';

    /** Request
     **********/
    if( (CDBLayer::GetInstance()->IsOpen()===TRUE)
     && IsScalarNotEmpty(PBR_DB_DBN)
     && IsStringNotEmpty($sLogin)
     && IsStringNotEmpty($sSession)
     && IsStringNotEmpty($sInet)
     && ($pContact->GetIdentifier()>0) )
    {
        /** Start transaction
         ********************/
        CDBLayer::GetInstance()->BeginTransaction($sLogin);

        try
        {
            /** Delete rents
             ***************/

            // Prepare
            $sSQL = 'DELETE FROM `'.PBR_DB_DBN.'`.`reservation` WHERE `idcontact`=:iIdentifier';
            $pPDOStatement = CDBLayer::GetInstance()->GetDriver()->prepare($sSQL);
            // Bind
            $pPDOStatement->bindValue(':iIdentifier',$pContact->GetIdentifier(),PDO::PARAM_INT);
               // Execute
               $pPDOStatement->execute();
               // Count
               $iReturn = $pPDOStatement->rowCount();
               // Free resource
               $pPDOStatement = NULL;

               /** Delete contact
                *****************/

               // Prepare
               $sSQL = 'DELETE FROM `'.PBR_DB_DBN.'`.`contact` WHERE `idcontact`=:iIdentifier';
               $pPDOStatement = CDBLayer::GetInstance()->GetDriver()->prepare($sSQL);
            // Bind
            $pPDOStatement->bindValue(':iIdentifier',$pContact->GetIdentifier(),PDO::PARAM_INT);
               // Execute
               $pPDOStatement->execute();
               // Count
               $iReturn = $iReturn + $pPDOStatement->rowCount();

               /** Commit transaction
             *********************/
            CDBLayer::GetInstance()->Commit($sLogin);

        }
        catch(PDOException $e)
        {
            $iReturn = FALSE;
            $sMessage = $e->getMessage();
               CDBLayer::GetInstance()->RollBack($sLogin);
        }//try

        // Free resource
        $pPDOStatement = NULL;

    }//if( ...

    // Error
    ErrorDBLog( $sLogin, $sErrorTitle, $sMessage, $iReturn, TRUE);

    return $iReturn;
}
