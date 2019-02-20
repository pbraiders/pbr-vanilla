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
if( !defined('PBR_VERSION') || !defined('PBR_DB_LOADED') || !defined('PBR_CONTACT_LOADED') )
    die('-1');

/**
  * function: ContactUpdate
  * description: Update a contact.
  * parameters: STRING|sLogin   - login identifier
  *             STRING|sSession - session identifier
  *             STRING|sInet    - concatenation of IP and USER_AGENT
  *           CContact|pContact - contact
  * return: BOOLEAN - FALSE if an exception occures
  *         or
  *         INTEGER - >0 is OK. Number of row Updated.
  *                   -1 when a private error occures.
  *                   -2 when an authentication error occures.
  *                   -3 when an access denied error occures.
  *                   -4 when a duplicate error occures.
  * author: Olivier JULLIEN - 2010-02-04
  * update: Olivier JULLIEN - 2010-05-24 - use ErrorDBLog instead of CErrorList::AddDB(...) and CDb::GetInstance()->LogError(...)
  */
function ContactUpdate( $sLogin, $sSession, $sInet, &$pContact)
{
	/** Initialize
     *************/
    $iReturn=-1;
    $sMessage='';
    $sErrorTitle=__FUNCTION__;
    $sErrorTitle.='('.$sLogin.','.$sSession.',[obfuscated],...)';
    $iUserId=CUser::GetInstance()->GetUserBDIdentifier();

	/** Request
     **********/
    if( IsParameterScalarNotEmpty($sLogin)
        && IsParameterScalarNotEmpty($sSession)
        && IsParameterScalarNotEmpty($sInet)
        && !is_null($pContact)
        && $pContact->IsValid()
        && CDb::GetInstance()->IsOpen()===TRUE )
    {
    	// Set error title
    	$sErrorTitle=__FUNCTION__;
    	$sErrorTitle.='('.$sLogin.','.$sSession.',[obfuscated],'.$pContact->GetLastName().','.$pContact->GetFirstName().','.$pContact->GetTel().')';
        // Request
        if( ($iUserId>0) && CUser::GetInstance()->IsAuthenticated() )
        {
            try
            {
                // Prepare
	            $sSQL='UPDATE `'.PBR_DB_DBN.'`.`contact` SET `lastname`=:sLastName, `firstname`=:sFirstName, `tel`=:sTel, `email`=:sEmail, `address`=:sAddress, `address_more`=:sAddressMore, `city`=:sCity, `zip`=:sZip, `comment`=:sComment, `update_date`=SYSDATE(), `update_iduser`=:iUserId WHERE `idcontact`=:iIdentifier';
                $pPDOStatement = CDb::GetInstance()->PDO()->prepare($sSQL);
                // Bind
                $pPDOStatement->bindParam(':iIdentifier',$pContact->GetIdentifier(),PDO::PARAM_INT);
                $pPDOStatement->bindParam(':sLastName',$pContact->GetLastName(),PDO::PARAM_STR,40);
                $pPDOStatement->bindParam(':sFirstName',$pContact->GetFirstName(),PDO::PARAM_STR,40);
                $pPDOStatement->bindParam(':sTel',$pContact->GetTel(),PDO::PARAM_STR,40);
                $pPDOStatement->bindParam(':sEmail',$pContact->GetEmail(),PDO::PARAM_STR,255);
                $pPDOStatement->bindParam(':sAddress',$pContact->GetAddress(),PDO::PARAM_STR,255);
                $pPDOStatement->bindParam(':sAddressMore',$pContact->GetAddressMore(),PDO::PARAM_STR,255);
                $pPDOStatement->bindParam(':sCity',$pContact->GetCity(),PDO::PARAM_STR,255);
                $pPDOStatement->bindParam(':sZip',$pContact->GetZip(),PDO::PARAM_STR,8);
                $pPDOStatement->bindParam(':sComment',$pContact->GetComment(),PDO::PARAM_STR);
				$pPDOStatement->bindParam(':iUserId',$iUserId,PDO::PARAM_INT);
	            // Execute
	            $pPDOStatement->execute();
				// Count
            	$iReturn=$pPDOStatement->rowCount();
            }
            catch(PDOException $e)
            {
                $iReturn=FALSE;
                $sMessage=$e->getMessage();
            }//try

            // Free resource
            $pPDOStatement=NULL;
		}
        else
        {
        	//Authentication failed
        	$iReturn=-2;
        }//if( ($iUserId>0) && CUser::GetInstance()->IsAuthenticated() )
    }//if( IsParameterScalarNotEmpty(

    // Error
    ErrorDBLog( $sLogin, $sErrorTitle, $sMessage, $iReturn, TRUE);

    return $iReturn;
}

?>