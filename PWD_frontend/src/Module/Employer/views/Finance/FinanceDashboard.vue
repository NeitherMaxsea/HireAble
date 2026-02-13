<template>
  <section class="finance-page">
    <h2>Finance Dashboard</h2>
    <p>Demo placeholder for finance role redirect.</p>

    <div class="cards">
      <div class="card">
        <span>Total Payroll (Demo)</span>
        <strong>$125,400</strong>
      </div>
      <div class="card">
        <span>Pending Reimbursements</span>
        <strong>18</strong>
      </div>
      <div class="card">
        <span>Budget Utilization</span>
        <strong>72%</strong>
      </div>
    </div>
  </section>
</template>

<script setup>
</script>

<style scoped>
.finance-page {
  padding: 10px;
}

.finance-page h2 {
  margin: 0;
}

.finance-page p {
  color: #64748b;
  margin: 8px 0 16px;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
}

.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
}

.card span {
  font-size: 12px;
  color: #64748b;
}

.card strong {
  display: block;
  margin-top: 4px;
  color: #0f172a;
}
</style>
