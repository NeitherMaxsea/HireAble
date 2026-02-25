<template>
  <div class="messages-page">
    <aside class="thread-list">
      <div class="thread-list-head">
        <h2>Messages</h2>
        <p>{{ threads.length }} conversations</p>
      </div>

      <div class="thread-search">
        <i class="bi bi-search"></i>
        <input v-model="search" type="text" placeholder="Search conversations..." />
      </div>

      <div class="thread-items">
        <button
          v-for="thread in filteredThreads"
          :key="thread.id"
          type="button"
          class="thread-item"
          :class="{ active: activeThread?.id === thread.id }"
          @click="selectThread(thread)"
        >
          <div class="thread-avatar">{{ thread.avatar }}</div>
          <div class="thread-copy">
            <div class="thread-top">
              <strong>{{ thread.name }}</strong>
              <span>{{ thread.time }}</span>
            </div>
            <p>{{ thread.preview }}</p>
          </div>
        </button>

        <div v-if="filteredThreads.length === 0" class="thread-empty">
          No conversations found.
        </div>
      </div>
    </aside>

    <section class="chat-panel">
      <div v-if="!activeThread" class="chat-empty">
        Select a conversation to open messages.
      </div>

      <template v-else>
        <div class="chat-panel-head">
          <div class="chat-contact">
            <div class="thread-avatar large">{{ activeThread.avatar }}</div>
            <div>
              <h3>{{ activeThread.name }}</h3>
              <p>{{ activeThread.role }}</p>
            </div>
          </div>

          <div class="chat-actions">
            <button type="button"><i class="bi bi-telephone"></i></button>
            <button type="button"><i class="bi bi-camera-video"></i></button>
            <button type="button"><i class="bi bi-three-dots"></i></button>
          </div>
        </div>

        <div class="chat-stream">
          <div
            v-for="msg in activeThread.messages"
            :key="msg.id"
            class="bubble-row"
            :class="msg.from === 'me' ? 'mine' : 'theirs'"
          >
            <div class="bubble">
              <p>{{ msg.text }}</p>
              <span>{{ msg.time }}</span>
            </div>
          </div>
        </div>

        <form class="chat-compose" @submit.prevent="sendMessage">
          <button type="button" class="icon-btn" aria-label="Attach file">
            <i class="bi bi-paperclip"></i>
          </button>
          <input
            v-model.trim="draftMessage"
            type="text"
            placeholder="Type a message..."
          />
          <button type="submit" class="send-btn">
            <i class="bi bi-send-fill"></i>
            <span>Send</span>
          </button>
        </form>
      </template>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from "vue"

const search = ref("")
const draftMessage = ref("")

const threads = ref([
  {
    id: "hr-1",
    name: "HR Department",
    role: "Company Recruiter",
    avatar: "HR",
    time: "10:42 AM",
    preview: "Please check your interview schedule for tomorrow.",
    messages: [
      { id: "m1", from: "them", text: "Hello! Your application is under review.", time: "9:10 AM" },
      { id: "m2", from: "me", text: "Thank you. Please let me know if you need more documents.", time: "9:15 AM" },
      { id: "m3", from: "them", text: "Please check your interview schedule for tomorrow.", time: "10:42 AM" },
    ],
  },
  {
    id: "support-1",
    name: "HireAble Support",
    role: "Platform Support",
    avatar: "HS",
    time: "Yesterday",
    preview: "You can update your skills anytime in the applicant portal.",
    messages: [
      { id: "s1", from: "them", text: "Welcome to HireAble!", time: "Yesterday" },
      { id: "s2", from: "them", text: "You can update your skills anytime in the applicant portal.", time: "Yesterday" },
    ],
  },
  {
    id: "finance-1",
    name: "Finance Department",
    role: "Job Posting Approval Updates",
    avatar: "FN",
    time: "Mon",
    preview: "A company job post was approved and is now open for applicants.",
    messages: [
      { id: "f1", from: "them", text: "A company job post was approved and is now open for applicants.", time: "Mon" },
    ],
  },
])

const activeThreadId = ref(threads.value[0]?.id || "")

const filteredThreads = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return threads.value
  return threads.value.filter((t) => {
    return [t.name, t.role, t.preview].join(" ").toLowerCase().includes(q)
  })
})

const activeThread = computed(() => {
  return threads.value.find((t) => t.id === activeThreadId.value) || filteredThreads.value[0] || null
})

function selectThread(thread) {
  activeThreadId.value = thread.id
}

function sendMessage() {
  const text = draftMessage.value.trim()
  if (!text || !activeThread.value) return

  const target = threads.value.find((t) => t.id === activeThread.value.id)
  if (!target) return

  target.messages.push({
    id: `m-${Date.now()}`,
    from: "me",
    text,
    time: "Now",
  })
  target.preview = text
  target.time = "Now"
  draftMessage.value = ""
}
</script>

<style scoped>
.messages-page {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 16px;
  min-height: calc(100vh - 170px);
}

.thread-list,
.chat-panel {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}

.thread-list {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.thread-list-head {
  padding: 16px;
  border-bottom: 1px solid #eef2f7;
}

.thread-list-head h2 {
  margin: 0;
  font-size: 18px;
  color: #0f172a;
}

.thread-list-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
}

.thread-search {
  margin: 12px 14px;
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 8px;
  border: 1px solid #dbe4ee;
  border-radius: 12px;
  padding: 0 10px;
  min-height: 42px;
  background: #f8fafc;
}

.thread-search i {
  color: #64748b;
  font-size: 14px;
}

.thread-search input {
  border: 0;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: #0f172a;
}

.thread-items {
  padding: 0 8px 10px;
  overflow-y: auto;
}

.thread-item {
  width: 100%;
  border: 1px solid transparent;
  background: #ffffff;
  display: grid;
  grid-template-columns: 42px 1fr;
  gap: 10px;
  align-items: start;
  text-align: left;
  padding: 10px;
  border-radius: 12px;
  cursor: pointer;
  margin-bottom: 6px;
}

.thread-item:hover {
  background: #f8fafc;
}

.thread-item.active {
  background: #ecfdf5;
  border-color: #bbf7d0;
}

.thread-avatar {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  background: linear-gradient(135deg, #15803d, #22c55e);
  color: #fff;
  display: grid;
  place-items: center;
  font-size: 12px;
  font-weight: 800;
}

.thread-avatar.large {
  width: 48px;
  height: 48px;
  font-size: 13px;
}

.thread-copy {
  min-width: 0;
}

.thread-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.thread-top strong {
  color: #0f172a;
  font-size: 13px;
}

.thread-top span {
  color: #94a3b8;
  font-size: 11px;
  white-space: nowrap;
}

.thread-copy p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.35;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.thread-empty {
  color: #94a3b8;
  font-size: 13px;
  padding: 18px 12px;
}

.chat-panel {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
}

.chat-empty {
  margin: auto;
  color: #94a3b8;
  font-size: 14px;
}

.chat-panel-head {
  padding: 14px 16px;
  border-bottom: 1px solid #eef2f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.chat-contact {
  display: flex;
  align-items: center;
  gap: 10px;
}

.chat-contact h3 {
  margin: 0;
  font-size: 15px;
  color: #0f172a;
}

.chat-contact p {
  margin: 3px 0 0;
  color: #64748b;
  font-size: 12px;
}

.chat-actions {
  display: flex;
  gap: 6px;
}

.chat-actions button {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid #dbe4ee;
  background: #fff;
  color: #475569;
  cursor: pointer;
}

.chat-stream {
  flex: 1;
  overflow-y: auto;
  background:
    radial-gradient(circle at 0% 0%, rgba(34, 197, 94, 0.06), transparent 35%),
    #f8fafc;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.bubble-row {
  display: flex;
}

.bubble-row.mine {
  justify-content: flex-end;
}

.bubble {
  max-width: min(72%, 560px);
  border-radius: 14px;
  padding: 10px 12px 8px;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
}

.bubble-row.theirs .bubble {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  color: #0f172a;
}

.bubble-row.mine .bubble {
  background: linear-gradient(135deg, #15803d, #22c55e);
  color: #ffffff;
}

.bubble p {
  margin: 0;
  line-height: 1.45;
  font-size: 13px;
}

.bubble span {
  display: block;
  margin-top: 6px;
  font-size: 10px;
  opacity: 0.85;
  text-align: right;
}

.chat-compose {
  padding: 12px 14px;
  border-top: 1px solid #eef2f7;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 8px;
  align-items: center;
  background: #fff;
}

.icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid #dbe4ee;
  background: #fff;
  color: #475569;
  cursor: pointer;
}

.chat-compose input {
  height: 40px;
  border-radius: 12px;
  border: 1px solid #dbe4ee;
  padding: 0 12px;
  outline: none;
  background: #f8fafc;
}

.chat-compose input:focus {
  border-color: #86efac;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}

.send-btn {
  height: 40px;
  border: 0;
  border-radius: 12px;
  padding: 0 14px;
  background: linear-gradient(135deg, #15803d, #22c55e);
  color: #fff;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  cursor: pointer;
}

@media (max-width: 1024px) {
  .messages-page {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .thread-list {
    max-height: 300px;
  }

  .chat-panel {
    min-height: 520px;
  }
}
</style>
