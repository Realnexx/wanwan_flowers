(() => {
  document.querySelectorAll('[data-psgc-address]').forEach(group => {
    const levels = ['region','province','city','barangay'];
    const fields = levels.map(level => group.querySelector(`[data-address-level="${level}"]`));
    const status = group.querySelector('.psgc-address-status');
    const retry = group.querySelector('.psgc-retry');
    let revision = 0, failed = null;
    const cache = new Map();
    function clear(index) {
      for (let i=index;i<fields.length;i++) {
        fields[i].replaceChildren(new Option(`Select ${levels[i]==='city'?'city / municipality':levels[i]}`,''));
        fields[i].disabled=true;
      }
    }
    async function load(index) {
      const ticket=++revision;
      clear(index); retry.hidden=true; failed=index;
      if (!fields[index-1].value) { status.textContent=''; group.removeAttribute('aria-busy'); return; }
      const url=new URL(group.dataset.endpoint,location.href);
      url.searchParams.set('level',['regions','provinces','cities','barangays'][index]);
      url.searchParams.set('parent',index===3?fields[2].value:fields[0].value);
      if(index===2)url.searchParams.set('province',fields[1].value);
      status.textContent='Loading address choices…';group.setAttribute('aria-busy','true');
      try {
        let rows=cache.get(url.href);
        if(!rows){
          const response=await fetch(url.href,{headers:{Accept:'application/json'}});
          if(!response.ok)throw new Error('Unavailable');
          rows=await response.json();if(!Array.isArray(rows))throw new Error('Invalid address choices');
          cache.set(url.href,rows);
        }
        if(ticket!==revision)return;
        rows.forEach(row=>fields[index].add(new Option(row.name,row.code)));
        fields[index].disabled=false;failed=null;
        status.textContent=rows.length?'':'No choices available for this selection.';
        if(index===1&&rows.length===1&&rows[0].code==='none'){
          fields[1].value='none';await load(2);
        }
      } catch(error){
        if(ticket!==revision)return;
        status.textContent='Address choices could not be loaded. Please retry.';retry.hidden=false;
      } finally {if(ticket===revision)group.removeAttribute('aria-busy');}
    }
    fields.forEach((field,index)=>{
      if(index>0&&!fields[index-1].value)field.disabled=true;
      if(index<3)field.addEventListener('change',()=>load(index+1));
    });
    retry.addEventListener('click',()=>{if(failed!==null)load(failed);});
    // A form cannot submit while children are loading or a required selection is absent.
    group.closest('form')?.addEventListener('submit',event=>{
      const incomplete=fields.find(field=>!field.value||field.disabled);
      if(incomplete||group.hasAttribute('aria-busy')){
        event.preventDefault();status.textContent='Please finish selecting your delivery address.';
        const focus=fields.find(field=>!field.disabled&&!field.value);focus?.focus();
      }
    });
  });
})();
