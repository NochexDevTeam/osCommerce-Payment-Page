<?php
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Nochex APC v0.1.1
  Copyright © Entrepreneuria Limited 2006

  Released under the GNU General Public License
*/
?>
<!-- Nochex APC //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('params' => 'class="menuBoxHeading"',
                     'text'  => BOX_HEADING_NOCHEX_APC_ADMIN,
                     'link'  => tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('selected_box')) . 'selected_box=nochex_apc'));

  if ($selected_box == 'nochex_apc' || $menu_dhtml == true) {
    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_NOCHEX_APC_TRANSACTIONS) . '?action=view">' . BOX_NOCHEX_APC_ADMIN_TRANSACTIONS . '</a><br>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- Nochex APC End //-->
