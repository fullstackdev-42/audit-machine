<?php

if (!array_key_exists('header', $this->data)) {
    $this->data['header'] = 'selectidp';
}
$this->data['header'] = 'IT Audit Machine';
$this->data['autofocus'] = 'dropdownlist';
$this->data['hideLanguageBar'] = true;
$this->includeAtTemplateBase('includes/header.php');

$translator = $this->getTranslator();
foreach ($this->data['idplist'] as $idpentry) {
    if (!empty($idpentry['name'])) {
        $translator->includeInlineTranslation(
            'idpname_'.$idpentry['entityid'],
            $idpentry['name']
        );
    } elseif (!empty($idpentry['OrganizationDisplayName'])) {
        $translator->includeInlineTranslation(
            'idpname_'.$idpentry['entityid'],
            $idpentry['OrganizationDisplayName']
        );
    }
    if (!empty($idpentry['description'])) {
        $translator->includeInlineTranslation('idpdesc_'.$idpentry['entityid'], $idpentry['description']);
    }
}
?>
    <div id="main">
        <div class="post">
            <div style="padding: 15px 0px;">
                <div>
                    <img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/Cybervisor_64x64.png" align="absmiddle" style="width: 64px; height: 64px;float: left;padding-right: 5px"/>
                    <h3 class="h-margin-0 header-h">Sign in to ITAM Portal</h3>
                    <p><?php echo $this->t('selectidp_full'); ?></p>
                    <div style="clear:both; border-bottom: 1px dotted #CCCCCC;margin-top: 15px"></div>
                </div>
                <div style="border-bottom: 1px dotted #CCCCCC;margin-top: 10px">
                    <form method="get" action="<?php echo $this->data['urlpattern']; ?>">
                        <input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>"/>
                        <input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>"/>
                        <input type="hidden" name="returnIDParam"
                               value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>"/>
                        <ul class="custom-ul">
                            <li class="custom-li">
                                <select id="dropdownlist" name="idpentityid">
                                    <?php
                                    usort($this->data['idplist'], function($idpentry1, $idpentry2) {
                                        return strcasecmp(
                                            $this->t('idpname_'.$idpentry1['entityid']),
                                            $this->t('idpname_'.$idpentry2['entityid'])
                                        );
                                    });

                                    foreach ($this->data['idplist'] as $idpentry) {
                                        echo '<option value="'.htmlspecialchars($idpentry['entityid']).'"';
                                        if (isset($this->data['preferredidp']) && $idpentry['entityid'] == $this->data['preferredidp']) {
                                            echo ' selected="selected"';
                                        }
                                        echo '>'.htmlspecialchars($this->t('idpname_'.$idpentry['entityid'])).'</option>';
                                    }
                                    ?>
                                </select>
                            </li>
                            <li class="custom-li">
                                <?php
                                if ($this->data['rememberenabled']) {
                                ?>
                                    <input type="checkbox" name="remember" value="1" />
                                    <label><?php echo $this->t('remember'); ?></label>
                                <?php
                                }
                                ?>
                            </li>
                            <li class="custom-li">
                                <button class="btn custom-btn" type="submit"><?php echo $this->t('select'); ?></button>
                            </li>
                        </ul>
                    </form>
                </div>
            </div>
        </div>
        <div class="auto-style2">
            <img alt="Only from Lazarus Alliance!" longdesc="Only from Lazarus Alliance: IT Audit Machine, IT Poic Machine, Continuum, Your Personal CXO, HORSE WIKI and The Security Tirfecta" src="https://continuumgrc.com/wp-content/uploads/2019/08/User-Portal-AD2019.gif" width="500">
        </div>
    </div>
    <img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bottom.png" id="bottom_shadow">
    
<?php $this->includeAtTemplateBase('includes/footer.php');
