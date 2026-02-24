/* jshint esversion: 6 */
(function ($) {
  "use strict";

  /* ============================================================
       Helpers
    ============================================================ */

  function sfcSpinner() {
    return '<span class="sfc-spinner"></span> ';
  }

  function sfcRiskColor(level) {
    var map = {
      safe: "#10b981",
      medium: "#f59e0b",
      high: "#ef4444",
      unknown: "#6b7280",
    };
    return map[level] || map.unknown;
  }

  function sfcRiskIcon(level) {
    var map = { safe: "✅", medium: "⚠️", high: "🚫", unknown: "❓" };
    return map[level] || map.unknown;
  }

  function sfcRiskLabel(level) {
    var map = {
      safe: "Safe",
      medium: "Medium Risk",
      high: "High Risk",
      unknown: "Unknown",
    };
    return map[level] || "Unknown";
  }

  /* ============================================================
       Manual Check Page
    ============================================================ */

  var $checkBtn = $("#sfc-check-btn");
  var $phoneInput = $("#sfc-phone-input");
  var $forceRefresh = $("#sfc-force-refresh");
  var $resultPanel = $("#sfc-result-panel");

  function buildResultHTML(data) {
    var level = data.risk_level || "unknown";
    var score = data.score || 0;
    var stats = data.stats || {};

    var statusLabel =
      level === "safe"
        ? "Successful"
        : level === "high"
          ? "High Risk"
          : level === "medium"
            ? "Medium Risk"
            : "Unknown";

    return (
      '<div class="sfc-result-panel__inner">' +
      '<div class="sfc-portal-style" style="margin:0; border:none; box-shadow:none;">' +
      '<div class="sfc-portal-header">' +
      '<span class="sfc-dot sfc-dot--' +
      level +
      '"></span>' +
      "<strong>" +
      statusLabel +
      "</strong>" +
      "</div>" +
      '<div class="sfc-portal-stats">' +
      '<div class="sfc-portal-stat">' +
      '<span class="sfc-box sfc-box--green"></span>' +
      '<span class="sfc-label">Success :</span>' +
      '<span class="sfc-value">' +
      (stats.success || 0) +
      "</span>" +
      "</div>" +
      '<div class="sfc-portal-stat">' +
      '<span class="sfc-box sfc-box--red"></span>' +
      '<span class="sfc-label">Cancellation :</span>' +
      '<span class="sfc-value">' +
      (stats.cancellation || 0) +
      "</span>" +
      "</div>" +
      "</div>" +
      '<div class="sfc-result-summary" style="margin-top:15px; border-top:1px solid #eee; padding-top:10px;">' +
      "<small>Risk Score: <b>" +
      score +
      "/100</b></small><br>" +
      '<small style="color:#7f8c8d;">' +
      (data.summary || "") +
      "</small>" +
      "</div>" +
      "</div>" +
      "</div>"
    );
  }

  function doManualCheck(phone, force) {
    if (!phone || phone.replace(/\D/g, "").length === 0) {
      alert("Please enter a phone number.");
      return;
    }

    $checkBtn
      .prop("disabled", true)
      .html(sfcSpinner() + (SFC.i18n.checking || "Checking…"));
    $resultPanel.hide();

    $.post(SFC.ajaxurl, {
      action: "sfc_manual_check",
      nonce: SFC.nonce,
      phone: phone,
      force: force ? "true" : "false",
    })
      .done(function (res) {
        if (res.success && res.data) {
          $resultPanel.find(".sfc-result-panel__inner").replaceWith("");
          $resultPanel.html(buildResultHTML(res.data)).show();
        } else {
          $resultPanel
            .html(
              '<div class="sfc-result-panel__inner"><p style="color:#ef4444;">' +
                (res.data && res.data.message
                  ? res.data.message
                  : SFC.i18n.error) +
                "</p></div>",
            )
            .show();
        }
      })
      .fail(function () {
        $resultPanel
          .html(
            '<div class="sfc-result-panel__inner"><p style="color:#ef4444;">' +
              SFC.i18n.error +
              "</p></div>",
          )
          .show();
      })
      .always(function () {
        $checkBtn
          .prop("disabled", false)
          .html('<span class="dashicons dashicons-search"></span> Check Now');
      });
  }

  $checkBtn.on("click", function () {
    doManualCheck($phoneInput.val(), $forceRefresh.is(":checked"));
  });

  $phoneInput.on("keypress", function (e) {
    if (e.which === 13)
      doManualCheck($(this).val(), $forceRefresh.is(":checked"));
  });

  // Recheck buttons in recent table
  $(document).on("click", ".sfc-recheck-btn", function () {
    var phone = $(this).data("phone");
    $phoneInput.val(phone);
    $("html, body").animate({ scrollTop: 0 }, 300);
    doManualCheck(phone, true);
  });

  /* ============================================================
       Settings Page – Test Credentials
    ============================================================ */

  $("#sfc-test-credentials").on("click", function () {
    var $btn = $(this);
    var $result = $("#sfc-test-result");

    $btn
      .prop("disabled", true)
      .html(sfcSpinner() + (SFC.i18n.testing || "Testing…"));
    $result.text("").attr("class", "sfc-inline-result");

    $.post(SFC.ajaxurl, {
      action: "sfc_test_credentials",
      nonce: SFC.nonce,
      api_key: $("#sfc_api_key").val(),
      secret_key: $("#sfc_secret_key").val(),
    })
      .done(function (res) {
        if (res.success) {
          $result
            .addClass("sfc-inline-result--success")
            .text("✅ " + res.data.message);
          if (
            res.data.balance &&
            res.data.balance.current_balance !== undefined
          ) {
            $result.append(" | Balance: ৳" + res.data.balance.current_balance);
          }
        } else {
          $result
            .addClass("sfc-inline-result--error")
            .text(
              "❌ " +
                (res.data && res.data.message
                  ? res.data.message
                  : "Connection failed."),
            );
        }
      })
      .fail(function () {
        $result.addClass("sfc-inline-result--error").text("❌ Request failed.");
      })
      .always(function () {
        $btn
          .prop("disabled", false)
          .html(
            '<span class="dashicons dashicons-awards"></span> Test Connection',
          );
      });
  });

  /* ============================================================
       Dashboard – Clear Cache
    ============================================================ */

  $("#sfc-clear-cache-btn").on("click", function () {
    if (!confirm(SFC.i18n.confirm_del || "Clear all cached results?")) return;

    var $btn = $(this);
    $btn.prop("disabled", true).html(sfcSpinner() + " Clearing…");

    $.post(SFC.ajaxurl, {
      action: "sfc_delete_cache",
      nonce: SFC.nonce,
    }).done(function (res) {
      if (res.success) {
        alert("✅ " + res.data.message);
        location.reload();
      } else {
        alert(
          "❌ " + (res.data && res.data.message ? res.data.message : "Error."),
        );
        $btn
          .prop("disabled", false)
          .html('<span class="dashicons dashicons-trash"></span> Clear Cache');
      }
    });
  });

  /* ============================================================
       Order Meta Box – Run / Re-run Check
    ============================================================ */

  $(document).on("click", ".sfc-order-check-btn", function () {
    var $btn = $(this);
    var orderId = $btn.data("order-id");
    var $result = $btn.siblings(".sfc-order-result");

    $btn
      .prop("disabled", true)
      .html(sfcSpinner() + (SFC.i18n.checking || "Checking…"));
    $result
      .hide()
      .removeClass("sfc-order-result--success sfc-order-result--error");

    $.post(SFC.ajaxurl, {
      action: "sfc_order_check",
      nonce: SFC.nonce,
      order_id: orderId,
    })
      .done(function (res) {
        if (res.success && res.data) {
          var d = res.data;
          var icon = sfcRiskIcon(d.risk_level);
          var label = sfcRiskLabel(d.risk_level);
          var color = sfcRiskColor(d.risk_level);
          $result
            .addClass("sfc-order-result--success")
            .html(
              "<strong>" +
                icon +
                " " +
                label +
                "</strong> (Score: " +
                d.score +
                "/100)<br>" +
                "<small>" +
                d.summary +
                "</small>",
            )
            .css("border-left", "3px solid " + color)
            .show();
          // Refresh badge if present in the same page
          $btn.html(
            '<span class="dashicons dashicons-update"></span> Re-run Check',
          );
        } else {
          $result
            .addClass("sfc-order-result--error")
            .text(
              res.data && res.data.message ? res.data.message : SFC.i18n.error,
            )
            .show();
          $btn.html(
            '<span class="dashicons dashicons-shield"></span> Run Fraud Check Now',
          );
        }
      })
      .fail(function () {
        $result.addClass("sfc-order-result--error").text(SFC.i18n.error).show();
        $btn
          .prop("disabled", false)
          .html(
            '<span class="dashicons dashicons-shield"></span> Run Fraud Check Now',
          );
      })
      .always(function () {
        $btn.prop("disabled", false);
      });
  });

  /* ============================================================
       Order List Intelligence Popup
    ============================================================ */

  $(document).on("click", ".sfc-view-fraud-details", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $btn = $(this);
    var orderId = $btn.data("order-id");

    $btn.find(".dashicons").replaceWith(sfcSpinner());

    $.post(SFC.ajaxurl, {
      action: "sfc_get_order_fraud_data",
      nonce: SFC.nonce,
      order_id: orderId,
    })
      .done(function (res) {
        if (res.success) {
          showFraudModal(res.data, orderId);
        } else {
          alert(res.data.message || "Error fetching data.");
        }
      })
      .fail(function () {
        alert("Request failed.");
      })
      .always(function () {
        $btn
          .find(".sfc-spinner")
          .replaceWith('<span class="dashicons dashicons-external"></span>');
      });
  });

  function showFraudModal(data, orderId) {
    var level = data.risk_level || "unknown";
    var stats = data.stats || {};

    var html =
      '<div class="sfc-modal-overlay" id="sfc-intelligence-modal">' +
      '<div class="sfc-modal">' +
      '<div class="sfc-modal__header">' +
      "<h3><span class=\"dashicons dashicons-shield-alt\"></span> Intelligence Report #" +
      orderId +
      "</h3>" +
      '<div style="display:flex; gap:8px;">' +
      '<button class="sfc-modal__refresh" data-order-id="' +
      orderId +
      '" title="Update from latest data"><span class="dashicons dashicons-update"></span></button>' +
      '<button class="sfc-modal__close"><span class="dashicons dashicons-no-alt"></span></button>' +
      "</div>" +
      "</div>" +
      '<div class="sfc-modal__body">' +
      buildResultHTML(data) +
      '<div style="text-align:center; margin-top:15px;">' +
      "<small style=\"color:#94a3b8;\">Checked at: " +
      (data.checked_at || "N/A") +
      "</small>" +
      "</div>" +
      "</div>" +
      "</div>" +
      "</div>";

    $("#sfc-intelligence-modal").remove(); // Prevent duplicates
    $("body").append(html);

    // Refresh inside modal
    $(".sfc-modal__refresh").on("click", function () {
      var $mBtn = $(this);
      $mBtn.addClass("sfc-spin-icon").attr("disabled", true);

      $.post(SFC.ajaxurl, {
        action: "sfc_get_order_fraud_data",
        nonce: SFC.nonce,
        order_id: orderId,
        force: "true",
      }).done(function (res) {
        if (res.success) {
          $("#sfc-intelligence-modal .sfc-modal__body").html(
            buildResultHTML(res.data) +
              '<div style="text-align:center; margin-top:15px;"><small style="color:#94a3b8;">Checked at: ' +
              (res.data.checked_at || "Just now") +
              "</small></div>",
          );
        }
      }).always(function() {
        $mBtn.removeClass("sfc-spin-icon").attr("disabled", false);
      });
    });

    // Close on click
    $(".sfc-modal__close").on("click", function () {
      $("#sfc-intelligence-modal").fadeOut(200, function () {
        $(this).remove();
      });
    });

    // Close on clicking outside (overlay)
    $(".sfc-modal-overlay").on("click", function (e) {
      if (e.target === this) {
        $(this).fadeOut(200, function () {
          $(this).remove();
        });
      }
    });
  }

  /* ============================================================
       Toggle Password Visibility
    ============================================================ */

  $(document).on("click", ".sfc-toggle-password", function () {
    var targetId = $(this).data("target");
    var $input = $("#" + targetId);
    var type = $input.attr("type") === "password" ? "text" : "password";
    $input.attr("type", type);
    $(this)
      .find(".dashicons")
      .toggleClass("dashicons-visibility", type === "password")
      .toggleClass("dashicons-hidden", type === "text");
  });
})(jQuery);
