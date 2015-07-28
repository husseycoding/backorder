var backorder = Class.create({
    afterInit: function() {
        $H(this.orderestimates).each(function(estimate) {
            $("order_item_" + estimate.key + "_title").insert({after: "<p class=\"dispatch-estimate\">" + this.estimatetext + ": <span>" + estimate.value + "</span></p>"});
        }.bind(this));
    }
});