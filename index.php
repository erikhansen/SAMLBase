<?php

include_once('./vendor/autoload.php');

$certData = array();
$certData['passphrase'] = 'test1234';
$certData['certificate'] = file_get_contents('./cert/example.crt');
$certData['privatekey'] = file_get_contents('./cert/example.pem');

$container = new Symfony\Component\DependencyInjection\ContainerBuilder();
$container->register('twig_loader', 'Twig_Loader_Filesystem')->addArgument('src/Wizkunde/SAML2PHP/Template/Twig');
$container->register('twig', 'Twig_Environment')->addArgument(new Symfony\Component\DependencyInjection\Reference('twig_loader'));

$container->register('SigningCertificate', 'Wizkunde\SAML2PHP\Certificate\Certificate')
    ->addArgument($certData['privatekey'])
    ->addArgument($certData['certificate'])
    ->addArgument($certData['passphrase']);

$container->register('EncryptionCertificate', 'Wizkunde\SAML2PHP\Certificate\Certificate')
    ->addArgument($certData['privatekey'])
    ->addArgument($certData['certificate'])
    ->addArgument($certData['passphrase']);

$container->register('signature', 'Wizkunde\SAML2PHP\Security\Signature')
    ->addMethodCall('setCertificate',array(new Symfony\Component\DependencyInjection\Reference('SigningCertificate')));

$container->register('unique_id_generator', 'Wizkunde\SAML2PHP\Configuration\UniqueID');
$container->register('timestamp_generator', 'Wizkunde\SAML2PHP\Configuration\Timestamp');
/**
 * Setup the Metadata resolve service
 */
$container->register('guzzle_http', 'GuzzleHttp\Client');
$container->register('resolver', 'Wizkunde\SAML2PHP\Metadata\ResolveService')
    ->addArgument(new Symfony\Component\DependencyInjection\Reference('guzzle_http'));

/**
 * Resolve the metadata
 */
$metadata = $container->get('resolver')->resolve(new \Wizkunde\SAML2PHP\Metadata\IDPMetadata(), 'http://idp.wizkunde.nl/simplesaml/saml2/idp/metadata.php');

$container->setParameter('NameID', 'testNameId');
$container->setParameter('Issuer', 'http://saml.dev.wizkunde.nl/');
$container->setParameter('MetadataExpirationTime', 604800);
$container->setParameter('SPReturnUrl', 'http://return.wizkunde.nl/');
$container->setParameter('ForceAuthn', 'false');
$container->setParameter('IsPassive', 'false');
$container->setParameter('NameIDFormat', 'testNameId');
$container->setParameter('ComparisonLevel', 'exact');

$container->register('binding_post', 'Wizkunde\SAML2PHP\Binding\Redirect')
    ->addMethodCall('setMetadata', array($metadata))
    ->addMethodCall('setContainer', array($container));

$container->register('binding_redirect', 'Wizkunde\SAML2PHP\Binding\Redirect')
    ->addMethodCall('setMetadata', array($metadata))
    ->addMethodCall('setContainer', array($container));

$redirectUrl = $container->get('binding_redirect')->request();
