<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$accountCredentials = new \Klevu\PhpSDK\Model\AccountCredentials(
    jsApiKey: (string)filter_input(INPUT_POST, 'jsApiKey', FILTER_SANITIZE_SPECIAL_CHARS),
    restAuthKey: (string)filter_input(INPUT_POST, 'restAuthKey', FILTER_SANITIZE_SPECIAL_CHARS),
);
$accountLookupService = new \Klevu\PhpSDK\Service\Account\AccountLookupService();
$accountFeaturesService = new \Klevu\PhpSDK\Service\Account\AccountFeaturesService();
?>
<html>
    <head>
        <title>Klevu PHP-SDK : Lookup Account</title>
        <style>
            *, *:before, *:after {
                box-sizing: border-box;
            }

            body {
                font-family: sans-serif !important;
                background: #f7fbfc;
                font-size: 0.875rem;
                line-height: 1.55;
                color: #5f5f5f;
            }

            fieldset {
                border: 1px solid #DDDDDD;
                border-radius: 5px;
                background: #FFFFFF;
                padding: 5px 10px;
                margin-bottom: 10px;
            }

            h1 {
                font-size: 1.4rem;
            }
            h2 {
                font-size: 1.3rem;
            }
            h3 {
                font-size: 1.2rem;
            }

            h1, h2, h3,
            fieldset legend {
                font-weight: 600;
                color: #0A4563;
            }

            form fieldset > p {
                display: flex;
            }
            form fieldset > p > label {
                flex-basis: 25%;
            }

            input[type="text"],
            select {
                background: #FFFFFF;
                border: 1px solid #DDDDDD;
                border-radius: 5px;
                padding: 5px 10px;
            }
            input[type="submit"] {
                color: #ffffff;
                background-color: #0A4563;
                border-color: #0A4563;
                border-radius: 5px;
                padding: 5px 10px;
                text-transform: uppercase;
            }

            code {
                color: #222222;
                font-family: "Lucida Console", Courier, monospace;
            }
            p, ul {
                margin: 0 0 10px 0;
            }
            p.error {
                color: #8a6d3b;
            }

            table {
                border: 1px solid #DDDDDD;
                border-radius: 5px;
                background: #FFFFFF;
                padding: 5px 10px;
                margin-bottom: 10px;
                min-width: 50%;
            }
            table tbody th {
                text-align: left;
                font-weight: 600;
                font-size: 0.875rem;
                color: #0A4563;
                padding: 5px;
            }
            table tbody td {
                padding: 5px;
                font-size: 0.875rem;
            }
            table tbody th + td {
                text-align: right;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Klevu PHP-SDK : Lookup Account</h1>
        </header>

        <section>
            <?php
                // Fields are not required or pattern-matched client-side to allow testing submission of missing data
                // In a production application, validation should be performed both client and server-side
            ?>
            <form action="" method="POST">
                <fieldset>
                    <legend>Account Credentials</legend>
                    <p>
                        <label for="jsApiKey">JS API Key</label>
                        <input type="text" id="jsApiKey" name="jsApiKey" placeholder="klevu-xxxxxx" value="<?= $accountCredentials->jsApiKey ?>" />
                    </p>
                    <p>
                        <label for="restAuthKey">REST AUTH Key</label>
                        <input type="text" id="restAuthKey" name="restAuthKey" value="<?= $accountCredentials->restAuthKey ?>" />
                    </p>
                </fieldset>
                <input type="submit" value="Lookup Account Details"/>
            </form>
        </section>

        <?php if ('POST' === $_SERVER['REQUEST_METHOD']): ?>
            <section>
                <header>
                    <h3>Submitted Data</h3>
                </header>
                <table>
                    <tbody>
                        <tr>
                            <th>JS API Key:</th>
                            <td><code><?= var_export($accountCredentials->jsApiKey, true) ?></code></td>
                        </tr>
                        <tr>
                            <th>REST AUTH Key:</th>
                            <td><code><?= var_export($accountCredentials->restAuthKey, true) ?></code></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section>
                <header>
                    <h3>Send Account Lookup Request</h3>
                </header>
                <?php
                    $account = null;
                    try {
                        $account = $accountLookupService->execute($accountCredentials);
                    } catch (\Klevu\PhpSDK\Exception\ValidationException $exception) {
                        echo '<p class="error"><strong>The credentials entered are invalid.</strong></p>';
                        echo '<ul><li>' . implode('</li><li>', $exception->getErrors()) . '</li></ul>';
                    } catch (\Klevu\PhpSDK\Exception\Api\BadRequestException $exception) {
                        echo '<p class="error"><strong>The request is invalid and was rejected by Klevu.</strong><br />';
                        echo $exception->getMessage();
                        echo '</p>';
                    } catch (\Klevu\PhpSDK\Exception\Api\BadResponseException $exception) {
                        echo '<p class="error"><strong>The Klevu API did not respond in an expected manner.</strong><br />';
                        echo $exception->getMessage();
                        echo '</p>';
                    } catch (\Klevu\PhpSDK\Exception\AccountNotFoundException $exception) {
                        echo '<p class="error"><strong>No account with those credentials was found.</strong></p>';
                    }
                ?>
                <?php if ($account): ?>
                    <p>The following account details were returned by Klevu:</p>
                    <table>
                        <tbody>
                            <tr>
                                <th>JS API Key:</th>
                                <td><code><?= var_export($account->getJsApiKey(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>REST AUTH Key:</th>
                                <td><code><?= var_export($account->getRestAuthKey(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Platform</th>
                                <td><code><?= var_export($account->getPlatform(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Is Active?</th>
                                <td><code><?= var_export($account->isActive(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Company Name:</th>
                                <td><code><?= var_export($account->getCompanyName(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><code><?= var_export($account->getEmail(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Indexing URL:</th>
                                <td><code><?= var_export($account->getIndexingUrl(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Search URL:</th>
                                <td><code><?= var_export($account->getSearchUrl(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Smart Category Merchandising URL:</th>
                                <td><code><?= var_export($account->getSmartCategoryMerchandisingUrl(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Analytics URL:</th>
                                <td><code><?= var_export($account->getAnalyticsUrl(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>JS URL:</th>
                                <td><code><?= var_export($account->getJsUrl(), true); ?></code></td>
                            </tr>
                            <tr>
                                <th>Tiers URL:</th>
                                <td><code><?= var_export($account->getTiersUrl(), true); ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <?php if ($account): ?>
                <section>
                    <header>
                        <h3>Send Account Features Request</h3>
                    </header>

                    <?php
                        $accountFeatures = null;
                        try {
                            $accountFeatures = $accountFeaturesService->execute($accountCredentials);
                        } catch (\Klevu\PhpSDK\Exception\ValidationException $exception) {
                            echo '<p class="error"><strong>The credentials entered are invalid.</strong></p>';
                            echo '<ul><li>' . implode('</li><li>', $exception->getErrors()) . '</li></ul>';
                        } catch (\Klevu\PhpSDK\Exception\Api\BadRequestException $exception) {
                            echo '<p class="error"><strong>The request is invalid and was rejected by Klevu.</strong><br />';
                            echo $exception->getMessage();
                            echo '</p>';
                        } catch (\Klevu\PhpSDK\Exception\Api\BadResponseException $exception) {
                            echo '<p class="error"><strong>The Klevu API did not respond in an expected manner.</strong><br />';
                            echo $exception->getMessage();
                            echo '</p>';
                        }
                    ?>

                    <?php if ($accountFeatures): ?>
                        <p>The following features are enabled on this account:</p>
                        <table>
                            <tbody>
                                <tr>
                                    <th>Smart Category Merchandising</th>
                                    <td><?= $accountFeatures->smartCategoryMerchandising ? 'Enabled' : 'Disabled' ?></td>
                                </tr>
                                <tr>
                                    <th>Smart Recommendations</th>
                                    <td><?= $accountFeatures->smartRecommendations ? 'Enabled' : 'Disabled' ?></td>
                                </tr>
                                <tr>
                                    <th>Preserve Layout</th>
                                    <td><?= $accountFeatures->preserveLayout ? 'Enabled' : 'Disabled' ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </body>
</html>
