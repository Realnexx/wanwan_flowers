(function () {
    'use strict';

    function savingButton(button) {
        if (!button || button.querySelector('.spinner-border, .spinner-grow')) return function () {};
        const original = button.innerHTML;
        const busy = button.getAttribute('aria-busy');
        const action = button.form && button.form.querySelector('input[name="action"]');
        const label = /^(edit|update)$/i.test(action ? action.value : '') || /updat/i.test(button.textContent) ? 'Updating...' : 'Saving...';
        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.style.setProperty('color', '#fff', 'important');
        spinner.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.textContent = label;
        text.style.setProperty('color', '#fff', 'important');
        text.setAttribute('role','status');
        button.replaceChildren(spinner, text);
        button.setAttribute('aria-busy', 'true');
        return function () {
            // Page handlers may have already restored or replaced the button.
            if (spinner.parentNode === button) button.innerHTML = original;
            if (busy === null) button.removeAttribute('aria-busy');
            else button.setAttribute('aria-busy', busy);
        };
    }

    let feedbackSequence = 0;
    const labels = {name:'Product name',category_id:'Category',price:'Selling price',cost_price:'Cost price',stock:'Initial stock',low_stock_threshold:'Low alert',max_stock_threshold:'Maximum stock',branch_id:'Assigned branch',unit_type:'Measurement unit',image:'Product image'};
    function fieldLabel(field) {
        return labels[field.name] || (field.labels && field.labels[0] ? field.labels[0].textContent.trim().replace(/\s+/g,' ') : field.getAttribute('aria-label') || 'This field');
    }
    function clearField(field) {
        field.classList.remove('is-invalid');field.removeAttribute('aria-invalid');
        const feedback=field._modalFeedback;
        if (feedback) {
            const described=(field.getAttribute('aria-describedby')||'').split(/\s+/).filter(id=>id && id!==feedback.id);
            if (described.length) field.setAttribute('aria-describedby',described.join(' ')); else field.removeAttribute('aria-describedby');
            feedback.remove(); delete field._modalFeedback;
        }
    }
    function fieldError(field,message) {
        clearField(field);
        const feedback=document.createElement('div');
        feedback.id='modal-field-error-'+(++feedbackSequence);feedback.className='invalid-feedback d-block';feedback.textContent=message;
        field.classList.add('is-invalid');field.setAttribute('aria-invalid','true');
        field.setAttribute('aria-describedby',((field.getAttribute('aria-describedby')||'')+' '+feedback.id).trim());
        (field.closest('.input-group') || field).after(feedback);field._modalFeedback=feedback;
    }
    function summary(form,message) {
        let box=form.querySelector('[data-validation-summary]');
        if (!box) {
            box=document.createElement('div');box.dataset.validationSummary='';box.className='alert alert-danger mb-3';box.setAttribute('role','alert');box.tabIndex=-1;
            (form.querySelector('.modal-body') || form).prepend(box);
        }
        if(box.textContent!==message)box.textContent=message;box.hidden=!message;
        return box;
    }
    function reveal(field) {
        const modal=field.closest('.modal');
        const panes=[];
        for (let parent=field.closest('.tab-pane');parent;parent=parent.parentElement.closest('.tab-pane')) panes.unshift(parent);
        panes.forEach(pane=>{
            const trigger=Array.from(modal.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"]')).find(button=>button.getAttribute('data-bs-target')==='#'+pane.id || button.getAttribute('href')==='#'+pane.id);
            if (trigger && window.bootstrap && bootstrap.Tab) bootstrap.Tab.getOrCreateInstance(trigger).show();
            else if (trigger) {
                Array.from(pane.parentElement.children).filter(node=>node.classList.contains('tab-pane')).forEach(node=>node.classList.toggle('active',node===pane));
                pane.classList.add('show');
                modal.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"]').forEach(button=>{const active=button===trigger;button.classList.toggle('active',active);button.setAttribute('aria-selected',String(active));});
            }
        });
        const focus=()=>{
            let target=field;
            if (!field.getClientRects().length) target=Array.from(modal.querySelectorAll('label[for]')).find(label=>label.htmlFor===field.id) || formSummary(field.form);
            if (!target) return;
            if (!target.matches('input,select,textarea,button,a[href],[tabindex]')) target.tabIndex=-1;
            target.focus({preventScroll:true});target.scrollIntoView({block:'center',behavior:'smooth'});
        };
        requestAnimationFrame(focus);
        const pane=field.closest('.tab-pane');
        if (pane && pane.classList.contains('fade')) setTimeout(focus,180);
    }
    function formSummary(form) { return form && form.querySelector('[data-validation-summary]'); }
    function productErrors(form) {
        const errors=new Map();
        const add=(field,message)=>{if(field && !errors.has(field))errors.set(field,message);};
        const field=name=>Array.from(form.elements).find(control=>control.name===name);
        const type=field('product_type') ? field('product_type').value : 'simple';
        const branchChoices=form.querySelectorAll('.product-branch-picker input');
        if(branchChoices.length&&!Array.from(branchChoices).some(input=>input.checked))add(branchChoices[0],'Select at least one branch.');
        Array.from(form.elements).forEach(control=>{
            if(type!=='recipe' && control.closest('#recipe-tab, #edit-recipe-tab'))return;
            if (control.willValidate && !control.validity.valid && !(control.name==='price' && ['ingredient','variant'].includes(type))) add(control,control.validationMessage);
        });
        const name=field('name');if(name && !name.value.trim())add(name,'Enter a product name.');
        const category=field('category_id');if(category && !category.value)add(category,'Choose a category.');
        const measurement=field('unit_type');if(measurement && !measurement.value)add(measurement,'Choose a measurement unit.');
        const price=field('price');if(price && !['ingredient','variant'].includes(type) && (!price.value || !(Number(price.value)>0)))add(price,'Enter a selling price greater than zero.');
        ['stock','low_stock_threshold','max_stock_threshold'].forEach(key=>{const input=field(key);if(input && !input.readOnly && type!=='variant' && (input.value==='' || !Number.isFinite(Number(input.value)) || Number(input.value)<0))add(input,'Enter a quantity of zero or more.');});
        const cost=field('cost_price');if(cost && cost.value!=='' && Number(cost.value)<0)add(cost,'Cost price cannot be negative.');
        const stock=field('stock');if(type==='recipe' && stock && !stock.readOnly && (!Number.isInteger(Number(stock.value)) || Number(stock.value)>100000))add(stock,'Enter a whole initial stock quantity from 0 to 100,000.');
        if(type==='combo'){
            const packageRows=form.querySelectorAll('.bundle-row'),seen=new Set();
            if(!packageRows.length)add(form.querySelector('.bundle-editor-rows'),'Add at least one package item.');
            packageRows.forEach(row=>{const product=row.querySelector('select'),qty=row.querySelector('[data-quantity]');if(!product.value)add(product,'Choose an included product.');else if(seen.has(product.value))add(product,'This product is already included.');seen.add(product.value);if(!Number.isInteger(Number(qty.value))||Number(qty.value)<1||Number(qty.value)>1000)add(qty,'Use a whole quantity from 1 to 1000.');});
        }
        if(type==='recipe') {
            const rows=form.querySelectorAll('.ingredient-row');
            const list=form.querySelector('#ingredients_list_create, #ingredients_list');
            if(!rows.length)add(list,'Add at least one recipe ingredient.');
            const seen=new Set();
            rows.forEach(row=>{
                const ingredient=row.querySelector('.ing-select'),quantity=row.querySelector('.ing-qty'),unit=row.querySelector('.ing-unit');
                if(ingredient && !ingredient.value)add(ingredient,'Choose an ingredient.');
                if(ingredient && ingredient.value){if(seen.has(ingredient.value))add(ingredient,'This ingredient is already included.');seen.add(ingredient.value);}
                if(quantity && (!quantity.value || !(Number(quantity.value)>0)))add(quantity,'Enter the ingredient quantity for one stock unit.');
                if(unit && !unit.value)add(unit,'Choose an ingredient unit.');
                if(quantity && unit && !['g','kg','ml','l'].includes(unit.value.toLowerCase()) && !Number.isInteger(Number(quantity.value)))add(quantity,'Counted ingredients require a whole quantity.');
            });
        }
        return errors;
    }
    function validateProduct(form,focus) {
        form.querySelectorAll('.is-invalid').forEach(clearField);
        const errors=productErrors(form);
        errors.forEach((message,field)=>fieldError(field,message));
        summary(form,errors.size ? 'Please correct '+errors.size+(errors.size===1?' highlighted field.':' highlighted fields.') : '');
        if(errors.size && focus){const [field,message]=errors.entries().next().value;reveal(field);if(window.showToast)showToast(fieldLabel(field)+': '+message,'error');}
        return !errors.size;
    }
    document.addEventListener('submit',function(event){
        const form=event.target;
        if(!form.matches('[data-inline-validation]'))return;
        form.dataset.validationAttempted='1';
        if(form.dataset.saving==='1' || !validateProduct(form,true)){event.preventDefault();event.stopImmediatePropagation();}
    },true);
    ['input','change'].forEach(type=>document.addEventListener(type,function(event){
        const form=event.target.form;
        if(form && form.matches('[data-inline-validation]') && form.dataset.validationAttempted==='1' && form.dataset.saving!=='1')validateProduct(form,false);
        else if(event.target._modalFeedback)clearField(event.target);
    }));

    function serverErrors(form,result,message) {
        form.querySelectorAll('.is-invalid').forEach(clearField);
        const isUserForm=!!form.closest('#createUserModal,#editUserModal');
        const hasFields=result && Object.keys(result.field_errors||{}).length>0;
        const imageOnly=isUserForm&&hasFields&&Object.keys(result.field_errors).every(name=>name==='image');
        const box=summary(form,isUserForm&&hasFields?'':message);
        if(imageOnly)return;
        let first=null;
        Object.entries(result && result.field_errors || {}).forEach(([name,error])=>{
            if(isUserForm&&name==='image')return;
            const field=Array.from(form.elements).find(control=>control.name===name);
            if(field){fieldError(field,String(error));if(!first)first=field;}
        });
        if(!first && form.matches('[data-inline-validation]')) {
            let name=/image|upload|jpg|png|webp|gif/i.test(message)?'image':/branch/i.test(message)?'branch_id':/stock|shortage|available|batch size/i.test(message)?'stock':/cost/i.test(message)?'cost_price':/price|amount/i.test(message)?'price':/name/i.test(message)?'name':null;
            first=name ? Array.from(form.elements).find(control=>control.name===name) : /ingredient|recipe|modifier|extra/i.test(message) ? form.querySelector('.ing-select, #ingredients_list_create, #ingredients_list') : null;
            if(first)fieldError(first,message);
        }
        reveal(first || box);
    }

    // Track the initiating control without changing existing AJAX form handlers.
    let initiatingButton = null;
    function trackButton(button) {
        initiatingButton = button;
        queueMicrotask(function () { if (initiatingButton === button) initiatingButton = null; });
    }
    document.addEventListener('click', function (event) {
        const button = event.target.closest('button');
        if (button && button.closest('.modal') && !button.matches('[data-bs-dismiss]')
            && /save|updat|creat|add/i.test(button.textContent)) trackButton(button);
    }, true);
    document.addEventListener('submit', function (event) {
        if (event.target.closest('.modal')) {
            trackButton(event.submitter || Array.from(event.target.elements).find(el => el.type === 'submit'));
        }
    }, true);
    if (window.jQuery) {
        window.jQuery.ajaxPrefilter(function (options, original, xhr) {
            if (!initiatingButton || !/^(POST|PUT|PATCH|DELETE)$/i.test(options.type || 'GET')) return;
            const restore = savingButton(initiatingButton);
            xhr.always(restore);
        });
    }

    // Native validation runs before submit, including external form buttons.
    let validationToastPending = false;
    document.addEventListener('invalid', function (event) {
        const field = event.target;
        if (!field.form || !field.form.closest('.modal')) return;
        event.preventDefault();
        fieldError(field,field.validationMessage);
        summary(field.form,'Please correct the highlighted fields.');
        if (validationToastPending) return;
        validationToastPending = true;
        const label = field.labels && field.labels[0];
        showToast((label ? label.textContent.trim() + ': ' : '') + field.validationMessage, 'error');
        reveal(field);
        setTimeout(function () { validationToastPending = false; }, 0);
    }, true);

    // Existing AJAX handlers preventDefault; only take over ordinary POST forms.
    window.addEventListener('submit', async function (event) {
        const form = event.target;
        const modal = form.closest('.modal');
        if (!modal || event.defaultPrevented || (form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        event.preventDefault();
        if (form.dataset.saving === '1') return;
        form.dataset.saving = '1';
        const previousBusy=form.getAttribute('aria-busy');form.setAttribute('aria-busy','true');
        const buttons = Array.from(modal.querySelectorAll('button[type="submit"], input[type="submit"]'));
        const states = buttons.map(button => button.disabled);
        const data = new FormData(form);
        if (event.submitter && event.submitter.name) data.append(event.submitter.name, event.submitter.value);
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) data.set('csrf_token', token.content);
        buttons.forEach(button => { button.disabled = true; });
        const restoreButtons = buttons.map(savingButton);
        let result=null, navigating=false;
        try {
            // A hidden input named "action" shadows the form.action property.
            const action = new URL(form.getAttribute('action') || window.location.href, document.baseURI);
            const response = await fetch(action.href, {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: { 'X-Modal-Form': '1', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const text = await response.text();
            try { result = JSON.parse(text); } catch (_) {
                const destination = new URL(response.url || action.href);
                if (response.redirected && destination.searchParams.get('route') === 'login') {
                    throw new Error('Your session has expired. Sign in again, then retry saving.');
                }
                if (response.status === 413) throw new Error('The server rejected the upload size. Try a smaller photo or ask your host to increase its upload limit.');
                if (response.status === 403) {
                    // Older deployments may still return the application's HTML access page.
                    const deniedPage = new DOMParser().parseFromString(text, 'text/html');
                    const reason = deniedPage.querySelector('.access-card .message');
                    if (reason && reason.textContent.trim()) throw new Error(reason.textContent.trim());
                    throw new Error('The server returned an HTML access-denied response (HTTP 403), rather than the application save response. Check the failed request in Network → Response to identify the hosting block or outdated server files.');
                }
                if (response.status >= 500) throw new Error('The server could not process this update (HTTP ' + response.status + '). Ask the administrator to check the hosting PHP error log.');
                throw new Error(text && text.length < 500 && !text.includes('<')
                    ? text : 'The server returned an unexpected response (HTTP ' + response.status + '). The update could not be confirmed. Check the hosting error log and deployed form files.');
            }
            if (!response.ok || result.success !== true) throw new Error(result.message || 'Unable to save. Please check the entered values.');
            navigating=true;
            window.location.assign(result.redirect || window.location.href);
        } catch (error) {
            navigating=false;
            serverErrors(form,result,error.message || 'Unable to save. Please try again.');
            showToast(error.message || 'Unable to save. Please try again.', 'error');
        } finally {
            if(!navigating) {
                delete form.dataset.saving;
                if(previousBusy===null)form.removeAttribute('aria-busy');else form.setAttribute('aria-busy',previousBusy);
                restoreButtons.forEach(restore => restore());
                buttons.forEach((button, index) => { button.disabled = states[index]; });
            }
        }
    });

    // Keep validation feedback consistent for older AJAX forms using SweetAlert.
    if (window.Swal) {
        const originalFire = Swal.fire.bind(Swal);
        Swal.fire = function (options) {
            if (options && typeof options === 'object' && options.icon === 'error' && document.querySelector('.modal.show')) {
                showToast(options.text || options.title || 'Unable to save.', 'error');
                return Promise.resolve({ isConfirmed: false, isDismissed: true });
            }
            return originalFire.apply(null, arguments);
        };
    }
})();
