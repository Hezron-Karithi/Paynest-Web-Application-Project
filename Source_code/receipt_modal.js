
function openReceiptModal(invoiceId){
    const modal = document.createElement('div');
    modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:20px;border-radius:8px">
            <h3>Select delivery method</h3>
            <button onclick="window.location='generate_receipt.php?invoice_id=${invoiceId}'">Open Generator</button>
            <br><br>
            <button onclick="this.closest('div').parentElement.remove()">Close</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
}
