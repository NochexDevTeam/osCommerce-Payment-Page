<?php
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Nochex APC v0.1.1
  Copyright © Entrepreneuria Limited 2006
  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (tep_not_null($action)) {
    switch ($_GET['action']) {
      case 'deleteconfirm':
        $nochex_txn_id = tep_db_prepare_input($_GET['txnID']);

        tep_db_query("delete from " . TABLE_NOCHEX_APC_TXN . " where transaction_id = '" . (int)$nochex_txn_id . "'");

        tep_redirect(tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] .'&action=view'));
        break;
    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NOCHEX_APC_TRANSACTIONS; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NOCHEX_APC_AMOUNT; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NOCHEX_APC_RESULT; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NOCHEX_APC_DATE; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $nochex_query_raw = "select * from " . TABLE_NOCHEX_APC_TXN . " order by transaction_id desc";
  $nochex_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $nochex_query_raw, $nochex_query_numrows);
  $nochex_query = tep_db_query($nochex_query_raw);
  while ($nochex_apc = tep_db_fetch_array($nochex_query)) {
    if (((!$_GET['txnID']) || (@$_GET['txnID'] == $nochex_apc['transaction_id'])) && (!$txnInfo)) {
      $txnInfo_array = $nochex_apc;
      $txnInfo = new objectInfo($txnInfo_array);
    }

    if ((is_object($txnInfo)) && ($nochex_apc['transaction_id'] == $txnInfo->transaction_id) ) {
      echo '              <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] . '&txnID=' . $nochex_apc['transaction_id'] . '&action=view') . '\'">' . "\n";
    } else {
      echo '              <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] . '&txnID=' . $nochex_apc['transaction_id'] . '&action=view') . '\'">' . "\n";
    }


?>
                <td class="dataTableContent"><?php echo $nochex_apc['nc_order_id']; ?></td>
                <td class="dataTableContent">&#163;<?php echo $nochex_apc['nc_amount']; ?></td>
                <td class="dataTableContent"><?php
                
                if(strtoupper($nochex_apc['nochex_response'])=="AUTHORISED"){
									echo("<span style=\"color:#007d00;\">AUTHORISED</span>, ");
                }elseif(strlen($nochex_apc['nochex_response'])>0){
									echo("<span style=\"color:#ff0000;\">".strtoupper($nochex_apc['nochex_response'])."</span>, ");
                }
                switch(strtolower($nochex_apc['nc_status'])){
									case "live":
										echo("<span style=\"color:#007d00;\"><b>Live</b></span>");
										break;
									case "test":
										echo("<span style=\"color:#ff0000;\"><b>TEST</b></span>");
										break;
									default:
										echo("Unknown");
										break;
								}
                
                ?></td>
                <td class="dataTableContent"><?php echo $nochex_apc['nc_transaction_date']; ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($txnInfo)) && ($nochex_apc['transaction_id'] == $txnInfo->transaction_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] . '&txnID=' . $nochex_apc['transaction_id']) . '&action=view">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $nochex_split->display_count($nochex_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_NOCHEX_APC_TRANSACTIONS); ?></td>
                    <td class="smallText" align="right"><?php echo $nochex_split->display_links($nochex_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();
  switch ($_GET['action']) {
    case 'view':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_VIEW_NOCHEX_APC_TRANSACTIONS . '</b>');

      $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] . '&txnID=' . $txnInfo->transaction_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
      $contents[] = array('text' => '<br><b>Nochex Transaction ID:</b> '. $txnInfo->nc_transaction_id);
      if (strtoupper($txnInfo->nochex_response) == "AUTHORISED") {
         $contents[] = array('text' => '<b>Nochex Result:</b> <b><font color="#007d00">'. $txnInfo->nochex_response . '</font></b>');
      } else {
         $contents[] = array('text' => '<b>Nochex Result:</b> <b><font color="#ff0000">'. $txnInfo->nochex_response . '</font></b>');
      }
      if(strtolower($txnInfo->nc_status)=="live"){
				$contents[] = array('text' => '<b>Payment Status Type:</b> <b><font color="#007d00">'. ucwords($txnInfo->nc_status) . '</font></b>');
			}else{
				$contents[] = array('text' => '<b>Payment Status Type:</b> <b><font color="#ff0000">'. ucwords($txnInfo->nc_status) . '</font></b>');
			}
      $contents[] = array('text' => '<b>Receiver Email:</b> '. $txnInfo->nc_from_email);
      $contents[] = array('text' => '<b>Sender Email:</b> '. $txnInfo->nc_to_email);
      $contents[] = array('text' => '<b>Total Amount:</b> &#163;'. $txnInfo->nc_amount);
      $contents[] = array('text' => '<b>OSc Order ID:</b> '. $txnInfo->nc_order_id);
      $contents[] = array('text' => '<b>Nochex Security Key:</b> '. $txnInfo->nc_security_key);
      break;
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_NOCHEX_APC_TRANSACTIONS . '</b>');

      $contents = array('form' => tep_draw_form('nochex_txn', FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] . '&txnID=' . $txnInfo->transaction_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>Transaction ID: ' . $txnInfo->transaction_id . '</b>');

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS, 'page=' . $_GET['page'] . '&txnID=' . $txnInfo->transaction_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>