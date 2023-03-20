<style>
	.v-select{
		margin-bottom: 5px;
        float: right;
        min-width: 200px;
        margin-left: 5px;
	}
	.v-select .dropdown-toggle{
		padding: 0px;
        height: 25px;
	}
	.v-select input[type=search], .v-select input[type=search]:focus{
		margin: 0px;
	}
	.v-select .vs__selected-options{
		overflow: hidden;
		flex-wrap:nowrap;
	}
	.v-select .selected-tag{
		margin: 2px 0px;
		white-space: nowrap;
		position:absolute;
		left: 0px;
	}
	.v-select .vs__actions{
		margin-top:-5px;
	}
	.v-select .dropdown-menu{
		width: auto;
		overflow-y:auto;
	}
    #orderDelivery label{
        font-size: 13px;
		margin-top: 3px;
    }
    #orderDelivery select{
        border-radius: 3px;
        padding: 0px;
		font-size: 13px;
    }
    #orderDelivery .form-group{
        margin-right: 10px;
    }
</style>
<div id="orderDelivery">
    <div class="row" style="border-bottom: 1px solid #ccc;padding: 5px 0;">
        <!-- <div class="col-md-12">
            <form class="form-inline" @submit.prevent="getProducts">
                <div class="form-group">
                    <label>Search Type</label>
                    <select class="form-control" v-model="searchType">
                        <option value="">All</option>
                        <option value="category">By Category</option>
                    </select>
                </div>

                <div class="form-group" style="display:none;" v-bind:style="{display: searchType == 'category' ? '' : 'none'}">
                    <label>Category</label>
                    <v-select v-bind:options="categories" v-model="selectedCategory" label="ProductCategory_Name"></v-select>
                </div>

                <div class="form-group" style="margin-top: -5px;">
                    <input type="submit" value="Search">
                </div>
            </form>
        </div> -->
    </div>

    <div class="row" style="display:none;margin-top: 15px;" v-bind:style="{display: orders.length > 0 ? '' : 'none'}">
        <div class="col-md-12" style="margin-bottom: 10px;">
            <a href="" @click.prevent="print"><i class="fa fa-print"></i> Print</a>
        </div>
        <div class="col-md-12">
            <div class="table-responsive" id="reportContent">
                <table class="table table-bordered table-condensed" id="orderDeliveryTable">
                    <thead>
                        <tr>
							<th>Invoice No.</th>
							<th>Date</th>
							<th>Customer Name</th>
							<th>Employee Name</th>
							<th>Saved By</th>
							<th>Sub Total</th>
							<th>VAT</th>
							<th>Discount</th>
							<th>Transport Cost</th>
							<th>Total</th>
							<th>Paid</th>
							<th>Due</th>
							<th>Note</th>
						</tr>
                    </thead>
                    <tbody>
                        <tr v-for="(sale, sl) in orders">
                            <td>{{ sale.SaleMaster_InvoiceNo }}</td>
							<td>{{ sale.SaleMaster_SaleDate }}</td>
							<td>{{ sale.Customer_Name }}</td>
							<td>{{ sale.Employee_Name }}</td>
							<td>{{ sale.AddBy }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_SubTotalAmount }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_TaxAmount }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_TotalDiscountAmount }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_Freight }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_TotalSaleAmount }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_PaidAmount }}</td>
							<td style="text-align:right;">{{ sale.SaleMaster_DueAmount }}</td>
							<td style="text-align:left;">{{ sale.SaleMaster_Description }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url();?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/vue-select.min.js"></script>
<script src="<?php echo base_url();?>assets/js/moment.min.js"></script>

<script>
    Vue.component('v-select', VueSelect.VueSelect);
    new Vue({
        el: '#orderDelivery',
        data(){
            return {
                searchType: '',
                orders: [],
                selectedProduct: null,
                categories: [],
                selectedCategory: null
            }
        },
        created(){
            this.getCategories();
            this.getOrders();
        },
        methods: {
            getCategories(){
                axios.get('/get_categories').then(res => {
                    this.categories = res.data;
                })
            },
            // getProducts(){
            //     let categoryId = '';
            //     if(this.searchType == 'category' && this.selectedCategory != null){
            //         categoryId = this.selectedCategory.ProductCategory_SlNo;
            //     }

            //     let data = {
            //         categoryId: categoryId
            //     }
            //     axios.post('/get_products', data).then(res => {
            //         this.products = res.data;
            //     })
            // },
            getOrders() {
                axios.get('/get_delivery_order').then(res => {
                    this.orders = res.data;
                })
            },
            async print(){
				let reportContent = `
					<div class="container">
                        <div class="row">
                            <div class="col-xs-12">
                                <h3 style="text-align:center">Order Delivery List</h3>
                            </div>
                        </div>
						<div class="row">
							<div class="col-xs-12">
								${document.querySelector('#reportContent').innerHTML}
							</div>
						</div>
					</div>
				`;

				var reportWindow = window.open('', 'PRINT', `height=${screen.height}, width=${screen.width}, left=0, top=0`);
				reportWindow.document.write(`
					<?php $this->load->view('Administrator/reports/reportHeader.php');?>
				`);

				reportWindow.document.body.innerHTML += reportContent;

				reportWindow.focus();
				await new Promise(resolve => setTimeout(resolve, 1000));
				reportWindow.print();
				reportWindow.close();
			}
        }
    })
</script>

