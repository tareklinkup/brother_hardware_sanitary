<div id="supplierOrderInvoice">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<supplier-order-invoice v-bind:purchase_id="purchaseId"></supplier-order-invoice>
		</div>
	</div>
</div>

<script src="<?php echo base_url();?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/components/supplierOrderInvoice.js"></script>
<script src="<?php echo base_url();?>assets/js/moment.min.js"></script>
<script>
	new Vue({
		el: '#supplierOrderInvoice',
		components: {
			supplierOrderInvoice
		},
		data(){
			return {
				purchaseId: parseInt('<?php echo $purchaseId;?>')
			}
		}
	})
</script>

