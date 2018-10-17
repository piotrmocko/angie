<?php
/**
 * @package   angi4j
 * @copyright Copyright (c)2009-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @author    Nicholas K. Dionysopoulos - http://www.dionysopoulos.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

defined('_AKEEBA') or die();

$hasHtaccess    = file_exists(dirname(__FILE__) . '../../../.htaccess');
$hasHtaccessBak = file_exists(dirname(__FILE__) . '../../../htaccess.bak');
?>
<html>
<head>
    <title>ANGIE - Akeeba Next Generation Installation Engine v. <?php echo AKEEBA_VERSION ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="text/javascript" src="template/angie/js/jquery.js"></script>
    <script type="text/javascript" src="template/angie/js/jquery.simulate.js"></script>
    <script type="text/javascript" src="template/angie/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="template/angie/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="template/angie/css/bootstrap-responsive.min.css"/>
    <link rel="stylesheet" type="text/css" href="template/angie/css/footer.css"/>
</head>
<body>
<div id="wrap">
    <div class="navbar navbar-inverse navbar-static-top">
        <div class="navbar-inner">
            <div class="container">
                <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="brand" href="#">ANGIE – Akeeba Next Generation Installer Engine
                    v.<?php echo AKEEBA_VERSION ?></a>
            </div>
        </div>
    </div>
    <div class="container">


        <div class="hero-unit">
            <h1>Incompatible PHP version</h1>
            <h3>
                Your server reports that you are using PHP <?php echo PHP_VERSION ?>. However, ANGIE requires PHP <?php echo $minPHP ?> or later to work.
            </h3>
            <p>
                For security, performance and efficiency reasons we only guarantee compatibility with obsolete PHP versions for software relased up to 6 months after the PHP version's
                <a href="http://php.net/eol.php">official end-of-life date</a>. You must upgrade to a newer version of
                PHP to restore your site. We strongly recommend using PHP <?php echo $recommendedPHP ?>.
            </p>
        </div>


        <div class="row-fluid">

            <div class="span8 pull-right" style="padding: 0 1em">
                <h3>How can I upgrade my PHP version?</h3>
                <p>
                    Generally, you need to ask your host. Usually you can do that through their hosting control panel.
                    Some servers require your host to reconfigure your site or your server; or move your site to a
                    different server.
                </p>

                <p>
                    We strongly recommend <strong>using PHP <?php echo $recommendedPHP ?></strong>.
                </p>

				<?php if (AUtilsServertechnology::isHtaccessSupported() != 0): ?>
                    <h4>Special instructions when changing the PHP version through your hosting control panel</h4>

                    <p class="alert alert-warning">
                        <span class="icon-warning-sign"></span>
                        If you do not follow the instructions below your restored site will not work
                    </p>

                    <p>If you can change the PHP version through the hosting control panel you are in fact creating or
                        modifying <code>.htaccess</code> files on your site. To prevent restoration problems <strong>you
                            should only change the PHP version for the <code>installation</code> folder of your
                            site</strong>. Do not change the PHP version for the main folder of your site (usually
                        called
                        <code>public_html</code>, <code>htdocs</code> or <code>www</code>) yet.</p>

                    <p>
						<?php if ($hasHtaccessBak): ?>
                            At the very end of the restoration process your site's
                            <code>.htaccess</code> file is restored and the
                            <code>installation</code> folder is removed. This happens when you click the Clean Up button.
						<?php else: ?>
                            At the very end of the restoration process the
                            <code>installation</code> folder is removed. This happens when you click the Clean Up button.
						<?php endif; ?>
                        Afterwards you need to go back to your hosting control panel. Change the PHP version for the
                        main
                        folder of your site (usually called <code>public_html</code>, <code>htdocs</code> or
                        <code>www</code>).
                    </p>

                    <h4>Tips for future updates of your <code>.htaccess</code> file</h4>
                    <p>
                        <em>This should be read as a final and optional step to be taken after the ones outlined
                            above</em>. </p>
                    <p>Go to your hosting control panel. Use the File Manager feature to
                        open the <code>.htaccess</code> file in your site's main (root) folder. Scroll all the way down.
                        Note down any AddHandler lines you see there. If you rebuild your site's <code>.htaccess</code>
                        file
                        you will need to manually append these lines at the end of the file for your site to work. If
                        you
                        are using our Admin Tools software and its .htaccess Maker feature you need to add these lines
                        at
                        the end of the “Custom .htaccess rules at the bottom of the file” option's content, removing any
                        other AddHandler lines which may exist therein.
                    </p>
				<?php endif; ?>
            </div>

            <div class="span4 pull-left"
                 style="padding: 0 1em; border: 1px solid #eee; border-radius: 0.5em; background-color: #fafafa">
                <h4>I believe the reported PHP version is wrong</h4>
                <p>
                    No, it's not. This is the version PHP itself reports.
                </p>

                <h4>My hosting control panel says I have a newer version</h4>
                <p>
                    Not entirely accurate. A server can have multiple PHP versions installed at the
                    same time. The version used by the hosting control panel and the version used by your site may be
                    &ndash; and usually are &ndash; different.
                </p>

				<?php if (AUtilsServertechnology::isHtaccessSupported() != 0): ?>
					<?php if (!$hasHtaccess): ?>
                        <h4>I had already upgraded my PHP version</h4>
                        <p>
                            Usually this happens by your host appending some code to the site's <code>.htaccess</code>
                            file.
                            For example, many cPanel-based hosts use this to enable PHP <?php echo $recommendedPHP ?>:
                        </p>
                        <pre>AddHandler application/x-httpd-php72 .php .php5 .php4 .php3</pre>
                        <p>
							<?php if ($hasHtaccessBak): ?>
                                The
                                <code>.htaccess</code> file of your backed up site has not been part of your backup or it was renamed or deleted after the extraction of your backup.
							<?php else: ?>
                                The <code>.htaccess</code> file of your backed up site was renamed to
                                <code>htaccess.bak</code> during the extraction of your backup. This is done automatically to prevent the restoration script from failing to load when the code in your existing .htaccess is not compatible with the server you are restoring to.
							<?php endif; ?>
                        </p>
                        <p>
                            As a result the .htaccess code which upgraded your PHP version is no longer
                            present. Therefore your server has switched to its default PHP
                            version, <?php echo PHP_VERSION ?>, which is not compatible with ANGIE. Hence the page you
                            are reading right now.
                        </p>
					<?php endif; ?>
				<?php endif; ?>
            </div>
        </div>
    </div>

    <div id="footer">
        <div class="container">
            <p class="muted credit">
                Copyright &copy;2006 &ndash; <?php echo date('Y') ?> Akeeba Ltd. All rights reserved.<br/>
                ANGIE is Free Software distributed under the
                <a href="http://www.gnu.org/licenses/gpl.html">GNU GPL version 3</a> or any later version published by
                the FSF.
            </p>
        </div>
    </div>
</div>
</body>
</html>
