<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */
require('../../../auditprotocol/config.php');
require('../../../auditprotocol/includes/db-core.php');
require('../../../auditprotocol/includes/helper-functions.php');

$dbh = la_connect_db();
$la_settings = la_get_settings($dbh);

$metadata[$la_settings['idp_entityId']] = [
    'entityid' => $la_settings['idp_entityId'],
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => $la_settings['idp_singleSignOnService'],
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => $la_settings['idp_singleSignOnService'],
        ],
    ],
    'SingleLogoutService' => [],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    ],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => $la_settings['idp_x509cert'],
        ],
    ],
];