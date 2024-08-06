<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$accountCredentials = new \Klevu\PhpSDK\Model\AccountCredentials(
    jsApiKey: (string)filter_input(INPUT_POST, 'jsApiKey', FILTER_SANITIZE_SPECIAL_CHARS),
    restAuthKey: (string)filter_input(INPUT_POST, 'restAuthKey', FILTER_SANITIZE_SPECIAL_CHARS),
);
$indexingUrl = (string)filter_input(INPUT_POST, 'indexingUrl', FILTER_SANITIZE_SPECIAL_CHARS);
$storeType = (string)filter_input(INPUT_POST, 'storeType', FILTER_SANITIZE_SPECIAL_CHARS);
$storeUrl = (string)filter_input(INPUT_POST, 'storeUrl', FILTER_SANITIZE_SPECIAL_CHARS);

$updateStoreFeedUrlService = new \Klevu\PhpSDK\Service\Account\UpdateStoreFeedUrlService();
?>
<html>
    <head>
        <title>Klevu PHP-SDK : Update Store Feed URL</title>
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
            <h1>Klevu PHP-SDK : Update Store Feed URL</h1>
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
                <fieldset>
                    <legend>Store Feed Information</legend>
                    <p>
                        <label for="indexingUrl">Feed URL</label>
                        <input type="text" id="indexingUrl" name="indexingUrl" placeholder="https://www.example.com/product-feed.xml" value="<?= $indexingUrl ?>" />
                    </p>
                    <?php
                        // In a production application, this should be retrieved from the AccountLookupService, eg
                        // $account = $accountLookupService->execute($accountCredentials);
                        // $storeType = $account->getPlatform();
                    ?>
                    <p>
                        <label for="storeType">Store Type</label>
                        <select id="storeType" name="storeType">
                            <option value=""></option>
                            <?php foreach (\Klevu\PhpSDK\Model\Platforms::cases() as $platform): ?>
                                <option value="<?= $platform->value ?>"
                                        <?= ($platform->value === $storeType) ? 'selected="selected"' : '' ?>
                                        ><?= $platform->value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="storeUrl">Store Name / URL</label>
                        <input type="text" id="storeUrl" name="storeUrl" placeholder="www.example.com" value="<?= $storeUrl ?>" />
                    </p>
                </fieldset>
                <input type="submit" value="Update Store Feed URL"/>
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
                        <tr>
                            <th>Feed URL:</th>
                            <td><code><?= var_export($indexingUrl, true) ?></code></td>
                        </tr>
                        <tr>
                            <th>Store Type:</th>
                            <td><code><?= var_export($storeType, true) ?></code></td>
                        </tr>
                        <tr>
                            <th>Store Name / URL:</th>
                            <td><code><?= var_export($storeUrl, true) ?></code></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section>
                <header>
                    <h3>Send Store Feed Update Request</h3>
                </header>
                <?php
                    $updateStatus = false;
                    try {
                        $updateStatus = $updateStoreFeedUrlService->execute(
                            accountCredentials: $accountCredentials,
                            indexingUrl: $indexingUrl,
                            storeType: $storeType,
                            storeUrl: $storeUrl
                        );
                    } catch (\Klevu\PhpSDK\Exception\ValidationException $exception) {
                        echo '<p class="error"><strong>The submitted data entered is invalid.</strong></p>';
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
                <?php if ($updateStatus): ?>
                    <p class="success"><strong>The Feed URL has been updated successfully.</strong></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </body>
</html>
