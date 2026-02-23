<template>
  <div class="app">
    <SidebarOpEmployer />

    <div class="main-wrapper">
      <NavbarEmployer />

      <main class="content">
        <div class="page-header">
          <div>
            <h2>Written Test Management</h2>
            <p class="subtitle">
              Build tests like a form editor: customize questions and answers freely, then preview and auto-score.
            </p>
          </div>
        </div>

        <section class="panel">
          <div class="panel-head">
            <h3>Test Form Details</h3>
          </div>
          <div class="form-grid">
            <label>
              Test Title
              <input v-model="formMeta.title" type="text" />
            </label>
            <label>
              Passing Score (%)
              <input v-model.number="formMeta.passingScore" type="number" min="1" max="100" />
            </label>
            <label class="wide">
              Description
              <textarea v-model="formMeta.description" rows="3"></textarea>
            </label>
          </div>
        </section>

        <div class="builder-preview-layout">
          <section class="panel">
            <div class="panel-head">
              <h3>Question Builder</h3>
              <button class="btn primary" @click="addQuestion">+ Add Question</button>
            </div>

            <div class="question-list">
              <article
                v-for="(question, qIndex) in questions"
                :key="question.id"
                class="question-card"
              >
                <div class="question-head">
                  <h4>Question {{ qIndex + 1 }}</h4>
                  <button
                    class="btn-link danger"
                    @click="removeQuestion(question.id)"
                  >
                    Remove
                  </button>
                </div>

                <div class="question-fields">
                  <label>
                    Category
                    <input v-model="question.category" type="text" placeholder="e.g. Safety" />
                  </label>
                  <label class="wide">
                    Question Text
                    <textarea
                      v-model="question.text"
                      rows="2"
                      placeholder="Type your question here..."
                    ></textarea>
                  </label>
                </div>

                <div class="options">
                  <p class="options-label">Options (select the correct answer)</p>
                  <div
                    v-for="(option, optIndex) in question.options"
                    :key="option.id"
                    class="option-row"
                  >
                    <input
                      type="radio"
                      :name="`correct-${question.id}`"
                      :checked="question.correctOptionId === option.id"
                      @change="setCorrectOption(question.id, option.id)"
                    />
                    <input
                      v-model="option.text"
                      type="text"
                      :placeholder="`Option ${optIndex + 1}`"
                    />
                    <button
                      class="icon-btn"
                      @click="removeOption(question.id, option.id)"
                      :disabled="question.options.length <= 2"
                      title="Remove option"
                    >
                      <i class="bi bi-trash3"></i>
                    </button>
                  </div>

                  <button class="btn ghost small" @click="addOption(question.id)">
                    + Add Option
                  </button>
                </div>
              </article>
            </div>
          </section>

          <section class="panel preview-panel">
            <div class="panel-head">
              <h3>Live Test Preview</h3>
            </div>

            <p class="preview-title">{{ formMeta.title || "Untitled Written Test" }}</p>
            <p class="preview-subtitle">{{ formMeta.description || "No description provided." }}</p>

            <div v-if="previewQuestions.length === 0" class="empty">
              Add valid questions (question text + at least 2 options + correct answer) to preview this test.
            </div>

            <div v-else>
              <div
                v-for="(question, index) in previewQuestions"
                :key="question.id"
                class="preview-question"
              >
                <p class="preview-question-title">
                  {{ index + 1 }}. {{ question.text }}
                </p>
                <label v-for="option in question.options" :key="option.id" class="preview-option">
                  <input
                    type="radio"
                    :name="`preview-${question.id}`"
                    :value="option.id"
                    v-model="previewAnswers[question.id]"
                  />
                  <span>{{ option.text }}</span>
                </label>
              </div>

              <div class="preview-actions">
                <button class="btn primary" @click="submitPreview">Submit Test</button>
              </div>

              <div v-if="result.score !== null" class="result" :class="result.passed ? 'pass' : 'fail'">
                Score: <strong>{{ result.score }}%</strong>
                <span>{{ result.passed ? "Passed" : "Failed" }}</span>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import SidebarOpEmployer from "@/components/sb-employer-operator.vue"
import NavbarEmployer from "@/components/nv-employer.vue"

export default {
  name: "WrittenTestManagement",
  components: {
    SidebarOpEmployer,
    NavbarEmployer
  },
  data() {
    return {
      idCounter: 220,
      formMeta: {
        title: "Operations Written Assessment",
        description: "Answer all items. Choose one best answer per question.",
        passingScore: 70
      },
      questions: [
        {
          id: 101,
          category: "Safety",
          text: "What should be checked before starting physical work tasks?",
          options: [
            { id: 201, text: "Safety equipment" },
            { id: 202, text: "Uniform color" },
            { id: 203, text: "Floor number" },
            { id: 204, text: "Shift length" }
          ],
          correctOptionId: 201
        },
        {
          id: 102,
          category: "Operations",
          text: "Which output is required before deployment?",
          options: [
            { id: 205, text: "Final evaluation" },
            { id: 206, text: "Payroll request" },
            { id: 207, text: "Attendance memo" },
            { id: 208, text: "Locker assignment" }
          ],
          correctOptionId: 205
        }
      ],
      previewAnswers: {},
      result: {
        score: null,
        passed: false
      }
    }
  },
  computed: {
    previewQuestions() {
      return this.questions
        .map((question) => {
          const validOptions = question.options.filter((option) => option.text.trim() !== "")
          const hasText = question.text.trim() !== ""
          const hasCorrect = validOptions.some((option) => option.id === question.correctOptionId)

          if (!hasText || validOptions.length < 2 || !hasCorrect) return null
          return {
            ...question,
            options: validOptions
          }
        })
        .filter(Boolean)
    }
  },
  methods: {
    createOption(text = "") {
      this.idCounter += 1
      return {
        id: this.idCounter,
        text
      }
    },
    createQuestion(category = "", text = "", optionTexts = ["", "", "", ""], correctIndex = 0) {
      this.idCounter += 1
      const options = optionTexts.map((entry) => this.createOption(entry))
      return {
        id: this.idCounter,
        category,
        text,
        options,
        correctOptionId: options[Math.max(0, Math.min(correctIndex, options.length - 1))].id
      }
    },
    addQuestion() {
      this.questions.push(this.createQuestion())
    },
    removeQuestion(questionId) {
      this.questions = this.questions.filter((question) => question.id !== questionId)
      delete this.previewAnswers[questionId]
    },
    addOption(questionId) {
      const question = this.questions.find((entry) => entry.id === questionId)
      if (!question) return
      question.options.push(this.createOption(""))
    },
    removeOption(questionId, optionId) {
      const question = this.questions.find((entry) => entry.id === questionId)
      if (!question || question.options.length <= 2) return

      question.options = question.options.filter((option) => option.id !== optionId)
      if (question.correctOptionId === optionId) {
        question.correctOptionId = question.options[0].id
      }
    },
    setCorrectOption(questionId, optionId) {
      const question = this.questions.find((entry) => entry.id === questionId)
      if (!question) return
      question.correctOptionId = optionId
    },
    submitPreview() {
      if (!this.previewQuestions.length) return
      let correctCount = 0

      this.previewQuestions.forEach((question) => {
        if (this.previewAnswers[question.id] === question.correctOptionId) {
          correctCount += 1
        }
      })

      const score = Math.round((correctCount / this.previewQuestions.length) * 100)
      this.result.score = score
      this.result.passed = score >= Number(this.formMeta.passingScore || 0)
    }
  }
}
</script>

<style scoped>
.app {
  display: flex;
  height: 100vh;
  background: #f5f7fb;
  overflow: hidden;
}

.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.content {
  padding: 24px;
  display: grid;
  gap: 16px;
  overflow-y: auto;
  min-height: 0;
}

.builder-preview-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(320px, 1fr);
  gap: 16px;
  align-items: start;
}

.preview-panel {
  position: sticky;
  top: 12px;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
}

.page-header h2 {
  margin: 0;
}

.subtitle {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
}

.panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
}

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.panel-head h3 {
  margin: 0;
  font-size: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.form-grid .wide {
  grid-column: 1 / -1;
}

label {
  font-size: 12px;
  color: #334155;
}

input:not([type="radio"]),
textarea {
  margin-top: 4px;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
  font-size: 13px;
}

.question-list {
  display: grid;
  gap: 12px;
}

.question-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  padding: 12px;
}

.question-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.question-head h4 {
  margin: 0;
}

.question-fields {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.question-fields .wide {
  grid-column: 1 / -1;
}

.options {
  margin-top: 10px;
}

.options-label {
  margin: 0 0 8px;
  font-size: 12px;
  color: #475569;
}

.option-row {
  display: grid;
  grid-template-columns: 18px 1fr auto;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.option-row input[type="radio"] {
  margin: 0;
  width: 16px;
  height: 16px;
}

.option-row input[type="text"] {
  margin-top: 0;
}

.icon-btn {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  border-radius: 8px;
  width: 34px;
  height: 34px;
  cursor: pointer;
}

.preview-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.preview-subtitle {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
}

.preview-question {
  margin-top: 14px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
}

.preview-question-title {
  margin: 0 0 8px;
  font-weight: 600;
}

.preview-option {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 6px;
  font-size: 13px;
}

.preview-option input[type="radio"] {
  margin: 0;
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.preview-actions {
  margin-top: 12px;
}

.result {
  margin-top: 10px;
  border-radius: 10px;
  padding: 10px 12px;
  display: flex;
  gap: 10px;
  align-items: center;
  font-size: 13px;
}

.result.pass {
  background: #dcfce7;
  color: #166534;
}

.result.fail {
  background: #fee2e2;
  color: #991b1b;
}

.empty {
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  color: #64748b;
  padding: 12px;
  border-radius: 10px;
  font-size: 13px;
}

.btn {
  border: none;
  border-radius: 10px;
  padding: 9px 12px;
  font-size: 13px;
  cursor: pointer;
}

.btn.primary {
  background: #2563eb;
  color: #ffffff;
}

.btn.ghost {
  background: #e2e8f0;
  color: #1f2937;
}

.btn.small {
  padding: 7px 10px;
  font-size: 12px;
}

.btn-link {
  border: none;
  background: none;
  color: #2563eb;
  cursor: pointer;
  font-size: 12px;
}

.btn-link.danger {
  color: #b91c1c;
}

@media (max-width: 800px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1100px) {
  .builder-preview-layout {
    grid-template-columns: 1fr;
  }

  .preview-panel {
    position: static;
    max-height: none;
    overflow: visible;
  }
}
</style>
