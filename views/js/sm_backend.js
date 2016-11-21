/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
(function (window, document, $) {

    $(document).ready(function () {

        var verifMailREGEX = window.verifMailREGEX ? window.verifMailREGEX : /^([\w+-]+(?:\.[\w+-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/;
        var lang = JSON.parse($(".smContentTranslation").text());
        var notify = {
            success: function (type, msg) {
                $("#" + type).removeClass("alert-success alert-danger").show().addClass("alert-success").text(msg);
            },
            error: function (type, msg) {
                $("#" + type).removeClass("alert-success alert-danger").show().addClass("alert-danger").text(msg);
            }
        };
        var campaignControls = {
            lock: function () {
                $(".campaignModalProgress").show();
            },
            unlock: function () {
                $(".campaignModalProgress").hide();
            }
        };

        $(".smFormGeneralTab").before("<button class='btn btn-default smAdminLogs'><i class='process-icon-configure'></i> " + lang.logsButton + "</button>");
        $(".smFormListTab").before("<button class='btn btn-default smAdminExportList'><i class='process-icon-duplicate'></i> " + lang.exportButton + "</button>");
        $(".smFormListTab").before("<button class='btn btn-default smAdminRefreshLists'><i class='process-icon-refresh'></i> " + lang.refreshListsButton + "</button>");
        $(".smFormEmailTab").before("<button class='btn btn-default smAdminCampaignConfigure'><i class='process-icon-edit'></i> " + lang.configureCampaignButton + "</button>");
        $(".smFormEmailTab").before("<button class='btn btn-default smAdminRefreshSender'><i class='process-icon-refresh'></i> " + lang.refreshSenderButton + "</button>");

        $(".smFormGeneralTab").parent().removeClass('col-lg-offset-3 col-lg-9');
        $(".smFormListTab").parent().removeClass('col-lg-offset-3 col-lg-9');
        $(".smFormEmailTab").parent().removeClass('col-lg-offset-3 col-lg-9');

        $(".force_full_width").removeClass('fixed-width-xl');

        $(".keywordDictionaryShow").click(function (e) {

            e.preventDefault();
            $(".campaignExtraHelp").show();
            $(".campaignBaseHelp").hide();
        });

        $(".sendCampaign").click(function () {

            var r = confirm(lang.CampaignLaunchConfirm);
            if (true === r) {

                saveCampaign().then(function () {

                    campaignControls.lock();

                    $.ajax({
                        url: $("#request_location").val(),
                        cache: false,
                        type: "POST",
                        data: {
                            ajax: 1,
                            launchCampaign: 1,
                            controller: 'AdminModules',
                            configure: 'sendmachine'
                        }, success: function (resp) {

                            campaignControls.unlock();

                            if ("ok" === resp) {
                                notify.success('smCampaignNotify', lang.campaignLaunchedSuccess);
                                campaignResetForm();
                            } else {
                                notify.error('smCampaignNotify', resp);
                            }
                        }, error: function () {

                            campaignControls.unlock();
                            notify.error('smCampaignNotify', lang.unexpectedError);
                        }
                    });
                });
            }
        });

        $(".saveDraft").click(function () {

            saveCampaign().then(function () {
                notify.success('smCampaignNotify', lang.draftSaveSuccess);
            });
        });

        $(".saveCampaign").click(function () {

            var r = confirm(lang.campaignSaveConfirm);
            if (true === r) {
                saveCampaign().then(function () {

                    campaignControls.lock();

                    $.ajax({
                        url: $("#request_location").val(),
                        cache: false,
                        type: "POST",
                        data: {
                            ajax: 1,
                            saveCampaign: 1,
                            controller: 'AdminModules',
                            configure: 'sendmachine'
                        }, success: function (resp) {

                            campaignControls.unlock();

                            if ("ok" === resp) {
                                notify.success('smCampaignNotify', lang.campaignSaveSuccess);
                                campaignResetForm();
                            } else {
                                notify.error('smCampaignNotify', resp);
                            }
                        }, error: function () {

                            campaignControls.unlock();
                            notify.error('smCampaignNotify', lang.unexpectedError);
                        }
                    });
                });
            }
        });

        $(".verifEmailSendmachine").click(function (e) {

            e.preventDefault();
            processEmailTesting();
        });

        $("#send_test_to").keypress(function (e) {

            if (13 === e.which) {
                e.preventDefault();
                processEmailTesting();
            }
        });

        $(".form-group").find('input[type="text"]').not('.preventFormSubmit').keypress(function (e) {

            if (13 === e.which) {
                e.preventDefault();
                $(e.target).closest('form').submit();
            }

        });

        $(".smAdminRefreshLists").click(function () {
            $("#do_refresh_lists").val(1);
        });
		
        $(".smAdminRefreshSender").click(function () {
            $("#do_refresh_sender").val(1);
        });

        $(".smAdminLogs").click(function (e) {

            e.preventDefault();
            showModal('readLogs');
        });

        $(".smActivityLogSelect").change(function () {

            var classPart = $(".smActivityLogSelect").val();
            $(".logSheet").hide();
            $(".log_sheet_" + classPart).show();
        });

        $(".smAdminCampaignConfigure").click(function (e) {

            e.preventDefault();
            showModal('configureCampaignModal');
        });

        $(".smAdminExportList").click(function (e) {

            e.preventDefault();
            showModal('confirmExportModal');
        });

        $(".confirmExportModalOkButton").click(function () {

            $("#do_export_subscribers").val(1);
            $("#do_export_subscribers").closest("form").submit();
            hideModal('confirmExportModal');
        });

        function campaignResetForm() {

            $("#campaign_name").val('');
            $("#campaign_subject").val('');
            $("#campaign_contact_list").val('');
            $("#campaign_sender").val('');
        }

        function saveCampaign() {

            campaignControls.lock();

            var data = {
                campaign_name: $("#campaign_name").val(),
                campaign_subject: $("#campaign_subject").val(),
                campaign_contact_list: $("#campaign_contact_list").val(),
                campaign_sender: $("#campaign_sender").val(),
                campaign_content: tinyMCE.get('campaign_content').getContent()
            };

            return $.ajax({
                url: $("#request_location").val(),
                cache: false,
                type: "POST",
                data: {
                    ajax: 1,
                    saveDraft: 1,
                    controller: 'AdminModules',
                    configure: 'sendmachine',
                    params: data
                }, success: function () {

                    campaignControls.unlock();
                }, error: function () {

                    campaignControls.unlock();
                    notify.error('smCampaignNotify', "An unexpected error occurred. Try again later.");
                }
            });
        }

        function processEmailTesting() {

            if (!verifMailREGEX.test($("#send_test_to").val())) {

                notify.error('smMailResultCheck', lang.testmailErrorAddress);
                return false;
            }

            $.ajax({
                url: $("#request_location").val(),
                cache: false,
                type: "POST",
                data: {
                    ajax: 1,
                    submit_send_testmail: 1,
                    controller: 'AdminModules',
                    configure: 'sendmachine',
                    testEmail: $("#send_test_to").val(),
                }, success: function (ret) {

                    var resp = ret.trim();

                    if ("ok" === resp) {

                        notify.success('smMailResultCheck', lang.testmailSuccess);
                    } else {

                        if (resp === "email_sending_disabled") {
                            notify.error('smMailResultCheck', lang.testmailErrorNotConfigured);
                        } else {
                            notify.error('smMailResultCheck', lang.testmailErrorGeneral);
                        }
                    }
                }
            });
        }

        function showModal(type) {
            $('#' + type).modal('show');
        }

        function hideModal(type) {
            $('#' + type).modal('hide');
        }

    });
})(window, document, $);