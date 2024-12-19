<?php
/********************************************************************************
 IT Audit Machine

 Patent Pending, Copyright 2000-2018 Lazarus Alliance. This code cannot be redistributed without
 permission from http://lazarusalliance.com

 More info at: http://lazarusalliance.com
 ********************************************************************************/
require('includes/init.php');
require('config.php');
require('includes/db-core.php');
require('includes/helper-functions.php');
require('portal-header.php');


if (isset($_GET['entity_id'])) {
    $_SESSION['la_client_client_id'] = $_GET['entity_id'];
    header("Location: client_account.php");
}

if (isset($_SESSION['la_client_client_id'])) {
    if ($_SESSION['la_client_client_id'] > 0 && $_SESSION['la_client_client_id'] != "0") {
        header("Location: client_account.php");
    }
}

//Connect to the database
$dbh = la_connect_db();
$userEntities = getEntityIds($dbh, $_SESSION['la_client_user_id']);

//Get user information from database table
$user_id = $_SESSION['la_client_user_id'];
$query = "SELECT * FROM ".LA_TABLE_PREFIX."ask_clients ORDER BY company_name";
$params = null;
try{
	$sth = la_do_query($query,$params,$dbh);
}catch(PDOException $e) {
	exit;
}
?>
<div class="content_body">
    <form action="sso-entity-select.php" method="post" name="entity-select" id="entity-select">
        <div style="display:none;">
            <input type="hidden" name="post_csrf_token" value="<?php echo noHTML($_SESSION['csrf_token']); ?>" />
        </div>
        <ul id="la_form_list" class="la_form_list">
            <?php
            while ($row = la_do_fetch_result($sth)) {
                $entity_id = $row['client_id'];
                ?>
                <a href="sso-entity-select.php?entity_id=<?php echo $entity_id; ?>">
                <div class="li-div-wrapper" style="margin-top: 5px;">
                    <div class="folder-div middle_entity_bar" style="line-height:40px;">
                        <li data-entity_id="<?php echo $entity_id; ?>" id="lientity_<?php echo noHTML($entity_id); ?>" class="form_visible" style="margin-left: 20px;">
                        <?php echo $row['company_name']; ?>
                        </li>
                    </div>
                </div>
                </a>
            <?php
            }
            ?>
        </ul>
    </form>
</div>
<?php
require('portal-footer.php');