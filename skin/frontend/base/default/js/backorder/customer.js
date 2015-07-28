var backorder = Class.create({
    afterInit: function() {
        $H(this.orderestimates).each(function(estimate) {
            $("order-item-row-" + estimate.key).down("h3").insert({after: "<p class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + estimate.value + "</span></p>"});
        }.bind(this));
    }
});