(()=>{
  let dragged=null;
  document.querySelectorAll('.lead-card[draggable="true"]').forEach(card=>{
    card.addEventListener('dragstart',e=>{
      dragged=card;
      card.classList.add('dragging');
      e.dataTransfer.effectAllowed='move';
      e.dataTransfer.setData('text/plain',card.dataset.id||'');
    });
    card.addEventListener('dragend',()=>{
      card.classList.remove('dragging');
      document.querySelectorAll('.pipeline-col').forEach(c=>c.classList.remove('drag-over'));
      dragged=null;
    });
  });
  document.querySelectorAll('.pipeline-col').forEach(col=>{
    col.addEventListener('dragover',e=>{e.preventDefault();col.classList.add('drag-over');});
    col.addEventListener('dragleave',e=>{if(!col.contains(e.relatedTarget))col.classList.remove('drag-over');});
    col.addEventListener('drop',async e=>{
      e.preventDefault();col.classList.remove('drag-over');
      if(!dragged)return;
      const id=dragged.dataset.id;
      const stage=col.dataset.stage;
      const oldStage=dragged.dataset.stage;
      if(!id||!stage||stage===oldStage)return;
      dragged.classList.add('saving');
      try{
        const r=await fetch('api/update-lead-stage.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,stage})});
        const data=await r.json().catch(()=>({ok:false}));
        if(!r.ok||!data.ok)throw new Error(data.error||'save_failed');
        window.location.reload();
      }catch(err){
        dragged.classList.remove('saving');
        alert('Could not move the lead. Please try again.');
      }
    });
  });
})();
