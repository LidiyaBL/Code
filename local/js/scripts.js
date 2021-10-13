var CustomCrm = BX.namespace('CustomCrm');

CustomCrm.isDealDetail = function () {

  	let url = new URL(document.URL);

  	return (url.pathname.indexOf('/crm/deal/details/') === 0);

};

CustomCrm.isCompanyDetail = function () {

  	let url = new URL(document.URL);

  	return (url.pathname.indexOf('/crm/company/details/') === 0);

};

CustomCrm.initDealsFucntions = function () {
  
	BX.addCustomEvent('oncrmentityupdateerror', CustomCrm.errorDeal);
  	BX.addCustomEvent('bx.ui.entityeditorfield:onlayout', CustomCrm.loadUF);
  	// BX.addCustomEvent('bx.ui.entityconfigurationmanager:oninitialize', CustomCrm.oninitializeUF);

};

CustomCrm.initCompanyFucntions = function () {
  
  	BX.addCustomEvent('oncrmentityupdateerror', CustomCrm.errorCompany);
	BX.addCustomEvent('bx.ui.entityeditorfield:onlayout', CustomCrm.loadUF);

};

CustomCrm.loadUF = function (param) {
	// console.log(param);
  	if (param._id == 'UF_CRM_1629283703') {
   		CustomCrm.loadContract();
		CustomCrm.loadMyCompany();
 	}
  
	if (param._id == 'UF_CRM_1628581148' && BX('progressbar-entity-editor') !== null) {
		CustomCrm.loadMyCompanyReq();
		console.log('eeeee');
	}
  	// if (param._id == 'UF_CRM_1631874131') {
   	// 	console.log(121212);
 	// }

};

CustomCrm.oninitializeUF = function (param) {
	// console.log(param);
	

};

CustomCrm.errorCompany = function (param) {

	if(param.error == 'Обновление компании отменено обработчиком события: "callable"') {
		document.querySelector('.ui-entity-section-control-error-text').innerHTML = 'Обновление компании отменено. Компания выгружена в 1С';
		
	}

};

CustomCrm.errorDeal = function (param) {

	if(param.error == 'Обновление сделки отменено обработчиком события: "callable"') {
		document.querySelector('.ui-entity-section-control-error-text').innerHTML = 'Обновление сделки отменено. Сделка выгружена в 1С';
		
	}

};

CustomCrm.loadContract = function () {

	let contractBox = document.querySelector('div[data-cid="UF_CRM_1629283703"]');
  	let dealId = null, contractEdit = BX.findChild(BX(contractBox), {class: 'ui-entity-editor-content-block'}, true, true);
	let input = null;

	let contractFields = {  
		type: document.querySelector('div[data-cid="UF_CRM_1625643130"]'),
		date: document.querySelector('div[data-cid="UF_CRM_1625118280"]'),
		name: document.querySelector('div[data-cid="UF_CRM_1625118250"]'),
		status: document.querySelector('div[data-cid="UF_CRM_1625123800"]'),
		number: document.querySelector('div[data-cid="UF_CRM_1631514835295"]'),
	};

	dealId = CustomCrm.getElementId('deal');

  	if(dealId > 0) {		

		if(contractBox) {
			let contractList = contractBox.querySelector('.deal-contract-select-box'), 
				text = contractBox.querySelector('.field-item--text'), 
				contractSelect = false, export1C = false, className = '', contractText = '';

			BX.ajax({
				url: '/local/ajax/crm.php',
				data: {mode: 'loadContract', dealId: dealId},
				method: 'POST',
				dataType: 'json',
				onsuccess: function(data){

					let html = '';

					if(contractList == null || !contractList) {
						contractList = BX.create({
							tag: 'div',
							attrs: { class: 'deal-contract-select-box field-item field-item--select' },
						});
					}	
									

					if(data.success) {
						let result = data.result;
						let items = result.contracts;
						export1C = result.export;					

						html += '<select><option value="0">Не выбрано</option>';
						for (var key in items) {
							let item = items[key];
							html += '<option value="' + key + '" ' + (item.BASE == 'Y' ? 'selected' : '') + '>\
								' + item.NAME + '\
							</option>';				

							if(item.BASE == 'Y') {
								contractSelect = key;
								contractText = item.NAME;
							}
								
						}
						
					}
					else {
						html += '<select><option value="0">Не выбрано</option>';
						contractText = 'Не выбрано';
						//html += '<option value="1"' + (data.new ? 'selected' : '') + '>Новый договор</option></select>';
					}		
					
					
					if(export1C)
						className = 'field-item--text active disabled';
					else 
						className = 'field-item--text active';

					if(text == null || !text) {
						text = BX.create({
							tag: 'span',
							text: (contractText.length <= 0 ? 'Не выбрано' : contractText),
							attrs: { class: className },
							events: {
								click: BX.proxy(CustomCrm.onMySelectClick, this)
							},
						});
					}
			
					if(contractSelect <= 1) {
						for (var key in contractFields) {							
							if(key == 'type' && contractSelect == 1)
								continue;

							let field = contractFields[key];
							if(field)
								BX.hide(BX(field));
						}
					}

					if(contractSelect > 1) {
						for (var key in contractFields) {
							if(key == 'type') {
								let field = contractFields[key];
								if(field)
									BX.hide(BX(field));
							}						
						}
					}
					
					
					contractList.innerHTML = /*(!contractSelect ? '<p class="deal_contract-warning">Договор не выбран</p>' : '') +*/ html;

					BX(contractBox).append(text);
					BX(contractBox).append(contractList);

					input = contractBox.querySelector('select');
					if(input) {
						input.addEventListener('change', function(event) {
							CustomCrm.onListContractChange(event);
						}); 
					}				

				},
				onfailure: function(){
			
				}
			});
		}
		if(contractEdit) {
			BX.hide(BX(contractEdit[0]));
		}
		
  	}
	else {
		for (var key in contractFields) {
			let field = contractFields[key];
			if(field)
				BX.hide(BX(field));					
		}
		BX.hide(BX(contractBox));	
	}
	

};

CustomCrm.loadMyCompany = function () {

  	let dealId = CustomCrm.getElementId('deal'),
		myCompanyBox = document.querySelector('div[data-cid="UF_CRM_1628581148"]'),
		myCompanyEdit = null;	

  	if(dealId > 0) {

		if(myCompanyBox) {
			myCompanyEdit = BX.findChild(BX(myCompanyBox), {class: 'ui-entity-editor-content-block'}, true, true);

			BX.ajax({
				url: '/local/ajax/crm.php',
				data: {mode: 'loadCompany', dealId: dealId},
				method: 'POST',
				dataType: 'json',
				onsuccess: function(data){
					if(data.success) {
						let result = data.result,
							items = result.mycompany,
							export1C = result.export, 
							selectBox = null,
							selectCompanyList = BX.create('select'),
							select = null,
							selectCompany = '',
							input = null,
							text = null,
							className = '';

						selectBox = BX.create({
							tag: 'div',
							attrs: { class: 'field-item field-item--select' },
						});
						if(myCompanyEdit) {
							BX.hide(BX(myCompanyEdit[0]));
						}

						//BX.selectUtils.addNewOption(selectCompanyList, 0, 'Не выбрано');
						for (let key in items) {
							let item = items[key];
							BX.selectUtils.addNewOption(selectCompanyList, item.ID, item.TITLE);							
							if(item.BASE == 'Y') {
								select = item.ID;
								selectCompany = item.TITLE;
							}								
						}
						BX.selectUtils.sortSelect(selectCompanyList);						
						if(select) {
							BX.selectUtils.selectOption(selectCompanyList, select);
						}	
						else {
							BX.selectUtils.selectOption(selectCompanyList, 0);
							selectCompany = 'Не выбрано';
						}					
									
						BX(selectBox).append(selectCompanyList);			

						if(export1C)
							className = 'field-item--text active disabled';
						else 
							className = 'field-item--text active';

						text = BX.create({
							tag: 'span',
							text: selectCompany,
							attrs: { class: className },
							events: {
								click: BX.proxy(CustomCrm.onMySelectClick, this)
							},
						});

						if(myCompanyBox) {
							BX(myCompanyBox).append(text);
							BX(myCompanyBox).append(selectBox);

							input = myCompanyBox.querySelector('select');
							input.addEventListener('change', function(event) {
								CustomCrm.onMyCompanyChange(event);
							});
						}
					}					
				},
				onfailure: function(){
			
				}
			});
		}

		
  	}
  	else {

		if(myCompanyBox) {
			myCompanyEdit = BX.findChild(BX(myCompanyBox), {class: 'ui-entity-editor-content-block'}, true, true);

			BX.ajax({
				url: '/local/ajax/crm.php',
				data: {mode: 'loadCompanyWithoutDeal'},
				method: 'POST',
				dataType: 'json',
				onsuccess: function(data){
					if(data.success) {
						let items = data.mycompany;
						let selectCompanyList = BX.create('select'), select = null, selectBox = null;

						selectBox = BX.create({
							tag: 'div',
							attrs: { class: 'field-item' },
						});
						BX.selectUtils.addNewOption(selectCompanyList, 0, 'Не выбрано');
						for (var key in items) {
							let item = items[key];
							BX.selectUtils.addNewOption(selectCompanyList, item.ID, item.TITLE);
							
							if(item.BASE == 'Y')
								select = item.ID;
						}
						//BX.selectUtils.sortSelect(selectCompanyList);
						// if(select) {
						// 	BX.selectUtils.selectOption(selectCompanyList, select);
						// 	BX('UF_CRM_1628581148').value = select;
						// }
						
						BX(selectBox).append(selectCompanyList);
						
						if(myCompanyEdit) {
							BX.hide(BX(myCompanyEdit[0]));
						}
						if(myCompanyBox)
							BX(myCompanyBox).append(selectBox);

						input = myCompanyBox.querySelector('select');
						input.addEventListener('change', function(event) {
							let input = event.target,
								company = input.value;
							BX('UF_CRM_1628581148').value = company;
						}); 

					}					
				},
				onfailure: function(){
			
				}
			});
		}

		
  	}


};

CustomCrm.loadMyCompanyReq = function () {
	
  	let container = document.getElementById('progressbar-entity-editor'),
	  	myCompanyBox = container.querySelector('div[data-cid="UF_CRM_1628581148"]'),
		myCompanyEdit = null;

	if(myCompanyBox) {
		myCompanyEdit = BX.findChild(BX(myCompanyBox), {class: 'ui-entity-editor-content-block'}, true, true);

		BX.ajax({
			url: '/local/ajax/crm.php',
			data: {mode: 'loadCompanyWithoutDeal'},
			method: 'POST',
			dataType: 'json',
			onsuccess: function(data){
				if(data.success) {
					let items = data.mycompany;
					let selectCompanyList = BX.create('select'), select = null, selectBox = null;

					selectBox = BX.create({
						tag: 'div',
						attrs: { class: 'field-item' },
					});
					BX.selectUtils.addNewOption(selectCompanyList, 0, 'Не выбрано');
					for (var key in items) {
						let item = items[key];
						BX.selectUtils.addNewOption(selectCompanyList, item.ID, item.TITLE);
					}
					
					BX(selectBox).append(selectCompanyList);
					
					if(myCompanyEdit) {
						BX.hide(BX(myCompanyEdit[0]));
					}
					if(myCompanyBox)
						BX(myCompanyBox).append(selectBox);

					input = myCompanyBox.querySelector('select');
					input.addEventListener('change', function(event) {
						let input = event.target,
							company = input.value;
						if(company > 0)
							BX('UF_CRM_1628581148').value = company;
					}); 

				}					
			},
			onfailure: function(){
		
			}
		});

	}
};


CustomCrm.onListContractChange = function (event) {

	let input = event.target,
		contract = input.value,
		dealId = null,
		message = document.querySelector('p.deal_contract-warning'),
		contractTypeBox = document.querySelector('div[data-cid="UF_CRM_1625643130"]'),
		contractTypeEdit = null;

	dealId = CustomCrm.getElementId('deal');

	// if(!input.checked) {
	// 	BX.show(BX(message));
	// 	contract = '';
	// }
	if(dealId > 0) {
		BX.ajax({
			url: '/local/ajax/crm.php',
			data: {mode: 'updateDealContract', dealId: dealId, contract: contract},
			method: 'POST',
			dataType: 'json',
			onsuccess: function(data){
				if(data.success) {

					window.location.reload();

					// if(contractTypeBox) {
					// 	if(contract == 1)
					// 		BX.show(BX(contractTypeBox));
					// 	else
					// 		BX.hide(BX(contractTypeBox));
					// }
				}					
			},
			onfailure: function(){
		
			}
		});
	}
	
};

CustomCrm.onMyCompanyChange = function (event) {

	let input = event.target,
		container = input.closest('div');
		company = input.value,
		dealId = null;

	dealId = CustomCrm.getElementId('deal');
	
	if(dealId > 0) {
		BX.ajax({
			url: '/local/ajax/crm.php',
			data: {mode: 'updateDealMyCompany', dealId: dealId, company: company},
			method: 'POST',
			dataType: 'json',
			onsuccess: function(data){
				CustomCrm.loadContract();	
				
				let text = BX.findPreviousSibling(
					BX(container),
					{'tag' : 'span'}, 
				);
				BX(text).innerHTML = input.options[input.options.selectedIndex].text;
				BX.addClass(BX(text), 'active');
				BX.removeClass(BX(container), 'active');
	
			},
			onfailure: function(){		
			}
		});
	}
	
};

CustomCrm.onMySelectClick = function (event) {

	let text = event.target,
		select = null;
	if(!BX.hasClass('disabled')) {
		select = BX.findNextSibling(
			BX(text),
			{'class' : 'field-item--select'}, 
		);
		BX.addClass(BX(select), 'active');
		BX.removeClass(BX(text), 'active');
	}		
	
};

CustomCrm.getElementId = function (type) {

	let matches = null, elementId = null;
	switch(type) {
		case 'deal':
			if (matches = window.location.href.match(/\/crm\/deal\/details\/([\d]+)\//i)) { 
				elementId = parseInt(matches[1]); 
			} 
			break;
	}

	return elementId;
};

BX.ready(function () { 

  	if (BX.CustomCrm.isDealDetail()) {

    	BX.CustomCrm.initDealsFucntions();

  	}

  	if (BX.CustomCrm.isCompanyDetail()) {

    	BX.CustomCrm.initCompanyFucntions();

  	}

});
