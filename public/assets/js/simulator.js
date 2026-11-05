"use strict";

(() => {
    const root = document.querySelector("[data-exam-runner]");
    if (!root) return;

    const questions = JSON.parse(document.querySelector("#exam-questions").textContent);
    const answers = JSON.parse(document.querySelector("#exam-answers").textContent);
    const serverFlags = JSON.parse(document.querySelector("#exam-flags").textContent || "[]");
    const total = questions.length;
    if (!total) return;

    const options = ["A", "B", "C", "D"];
    const token = root.dataset.attemptToken;
    const baseUrl = root.dataset.baseUrl.replace(/\/$/, "");
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const palette = document.querySelector("[data-palette-grid]");
    const palettePanel = document.querySelector("[data-palette-panel]");
    const backdrop = document.querySelector("[data-palette-backdrop]");
    const saving = document.querySelector("[data-saving]");
    const saveError = document.querySelector("[data-save-error]");
    const submitDialog = document.querySelector("[data-submit-dialog]");
    const submitSuccessDialog = document.querySelector("[data-submit-success-dialog]");
    const submitForm = document.querySelector("[data-submit-form]");
    const flags = new Set(JSON.parse(sessionStorage.getItem(`practice-flags-${token}`) || "[]"));
    serverFlags.forEach((questionId) => flags.add(String(questionId)));
    sessionStorage.setItem(`practice-flags-${token}`, JSON.stringify([...flags]));
    let currentIndex = 0;
    let saveQueue = Promise.resolve();
    let savePending = false;
    let submitStarted = false;
    let paletteReturnFocus = null;
    let deadline = Date.now() + Number(root.dataset.remainingSeconds) * 1000;

    const answerCount = () => Object.keys(answers).filter((key) => options.includes(answers[key])).length;

    const updateCsrf = (response) => {
        const nextToken = response.headers.get("X-CSRF-Token");
        if (!nextToken) return;
        csrfMeta.content = nextToken;
        submitForm.querySelector('input[name="_csrf_token"]').value = nextToken;
    };

    const setSaveState = (label, visible = true) => {
        saving.classList.toggle("hidden", !visible);
        saving.querySelector("[data-saving-label]").textContent = label;
    };

    const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

    /**
     * POST JSON with automatic retry on network failures and 5xx/429 responses
     * (weak mobile data drops requests). 4xx answers are final and returned as-is.
     */
    const postJson = async (url, body, attempts = 4) => {
        for (let attempt = 1; ; attempt++) {
            // A stalled mobile connection must never freeze the exam: give up after 15 s and retry.
            const controller = new AbortController();
            const timer = window.setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(url, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-Token": csrfMeta.content },
                    body: JSON.stringify(body),
                    signal: controller.signal
                });
                window.clearTimeout(timer);
                updateCsrf(response);
                const retryable = response.status >= 500 || response.status === 429;
                if (!retryable || attempt >= attempts) {
                    const payload = await response.json().catch(() => ({}));
                    return { response, payload };
                }
            } catch (networkError) {
                window.clearTimeout(timer);
                if (attempt >= attempts) throw networkError;
            }
            await wait(600 * attempt * attempt); // 0.6s, 2.4s, 5.4s
        }
    };

    /** The attempt was closed on the server (time up, or submitted from another tab). */
    const handleClosed = ({ response, payload }) => {
        if (response.status === 409 && (payload.expired || payload.submitted) && payload.result_url) {
            submitStarted = true;
            window.location.assign(payload.result_url);
            return true;
        }
        return false;
    };

    const saveAnswer = (questionId, selectedOption) => {
        savePending = true;
        saveError.classList.add("hidden");
        setSaveState("Saving…");
        saveQueue = saveQueue.then(async () => {
            const result = await postJson(`${baseUrl}/exam/${encodeURIComponent(token)}/answer`, { question_id: questionId, selected_option: selectedOption });
            if (handleClosed(result)) return;
            if (!result.response.ok) throw new Error(result.payload.error || "Answer could not be saved.");
            setSaveState("Saved");
            window.setTimeout(() => setSaveState("Saved", false), 1400);
        }).catch(() => {
            saveError.classList.remove("hidden");
            setSaveState("Not saved");
            savePending = false;
        }).finally(() => {
            savePending = false;
        });
        return saveQueue;
    };

    const renderPalette = () => {
        const answered = answerCount();
        document.querySelector("[data-answered-count]").textContent = String(answered);
        document.querySelector("[data-mobile-count]").textContent = `(${answered}/${total})`;
        palette.replaceChildren();
        questions.forEach((question, index) => {
            const button = document.createElement("button");
            button.type = "button";
            button.textContent = String(index + 1);
            button.setAttribute("aria-label", `Question ${index + 1}${answers[question.id] ? ", answered" : ", not answered"}${flags.has(String(question.id)) ? ", flagged" : ""}`);
            button.setAttribute("aria-current", index === currentIndex ? "step" : "false");
            button.className = "palette-item grid min-h-11 min-w-11 place-items-center rounded-lg border border-slate-300 bg-white text-sm font-extrabold text-slate-700 transition hover:border-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600";
            if (answers[question.id]) button.classList.add("bg-primary", "text-white", "border-primary");
            if (flags.has(String(question.id))) button.classList.add("bg-amber-400", "text-ink", "border-amber-500");
            if (index === currentIndex) button.classList.add("ring-2", "ring-primary-600", "ring-offset-2");
            button.addEventListener("click", () => {
                currentIndex = index;
                renderQuestion();
                closePalette();
            });
            palette.append(button);
        });
    };

    const renderQuestion = () => {
        const question = questions[currentIndex];
        document.querySelector("[data-question-count]").textContent = `Question ${currentIndex + 1} of ${total}`;
        document.querySelector("[data-question-text]").textContent = question.question_text;
        const progress = document.querySelector("[data-progress]");
        progress.classList.remove("w-[10%]", "w-[20%]", "w-[30%]", "w-[40%]", "w-[50%]", "w-[60%]", "w-[70%]", "w-[80%]", "w-[90%]", "w-full");
        progress.classList.add(currentIndex === total - 1 ? "w-full" : `w-[${Math.ceil(((currentIndex + 1) / total) * 10) * 10}%]`);
        const fieldset = document.querySelector("[data-options]");
        fieldset.replaceChildren();
        const legend = document.createElement("legend");
        legend.className = "sr-only";
        legend.textContent = "Choose one answer";
        fieldset.append(legend);
        options.forEach((letter) => {
            const label = document.createElement("label");
            label.className = "option-row flex min-h-14 cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-white p-4 transition duration-150 hover:border-primary/60 hover:bg-blue-50/60 focus-within:ring-2 focus-within:ring-primary-600 focus-within:ring-offset-2";
            const input = document.createElement("input");
            input.type = "radio";
            input.name = `question-${question.id}`;
            input.value = letter;
            input.className = "mt-1 h-5 w-5 shrink-0 accent-primary";
            input.checked = answers[question.id] === letter;
            const badge = document.createElement("span");
            badge.className = "grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-extrabold text-slate-700";
            badge.textContent = letter;
            const text = document.createElement("span");
            text.className = "pt-0.5 text-sm leading-6 text-slate-800 sm:text-base";
            text.textContent = question[`option_${letter.toLowerCase()}`];
            input.addEventListener("change", () => {
                answers[question.id] = letter;
                renderPalette();
                saveAnswer(question.id, letter);
            });
            label.append(input, badge, text);
            fieldset.append(label);
        });

        const previousButtons = document.querySelectorAll("[data-previous]");
        previousButtons.forEach((button) => { button.disabled = currentIndex === 0; });
        const nextButtons = document.querySelectorAll("[data-next]");
        nextButtons.forEach((button) => {
            button.textContent = currentIndex === total - 1 ? "Review & submit" : (button.dataset.mobileNext ? "Next" : "Next question");
        });
        const flagButton = document.querySelector("[data-flag]");
        const flagged = flags.has(String(question.id));
        flagButton.setAttribute("aria-pressed", String(flagged));
        flagButton.querySelector("[data-flag-label]").textContent = flagged ? "Flagged" : "Flag";
        renderPalette();
    };

    const saveFlag = async (questionId, flagged) => {
        const result = await postJson(`${baseUrl}/exam/${encodeURIComponent(token)}/flag`, { question_id: questionId, flagged });
        if (handleClosed(result)) return;
        if (!result.response.ok) {
            throw new Error(result.payload.error || "Flag could not be updated.");
        }
    };

    const toggleFlag = async () => {
        const id = String(questions[currentIndex].id);
        const nextFlagged = !flags.has(id);
        if (nextFlagged) flags.add(id); else flags.delete(id);
        sessionStorage.setItem(`practice-flags-${token}`, JSON.stringify([...flags]));
        renderQuestion();
        try {
            await saveFlag(Number(questions[currentIndex].id), nextFlagged);
        } catch (error) {
            if (nextFlagged) flags.delete(id); else flags.add(id);
            sessionStorage.setItem(`practice-flags-${token}`, JSON.stringify([...flags]));
            renderQuestion();
            window.alert(error.message || "Unable to save the flag state right now.");
        }
    };

    const openPalette = (trigger) => {
        paletteReturnFocus = trigger || document.querySelector("[data-palette-open]");
        backdrop.hidden = false;
        palettePanel.setAttribute("aria-hidden", "false");
        palettePanel.inert = false;
        palettePanel.classList.remove("translate-y-full");
        document.querySelector("[data-palette-open]").setAttribute("aria-expanded", "true");
        document.body.classList.add("overflow-hidden");
        document.querySelector("[data-palette-close]").focus();
    };

    const closePalette = () => {
        palettePanel.classList.add("translate-y-full");
        backdrop.hidden = true;
        document.body.classList.remove("overflow-hidden");
        const mobile = window.matchMedia("(max-width: 767px)").matches;
        palettePanel.setAttribute("aria-hidden", String(mobile));
        palettePanel.inert = mobile;
        document.querySelector("[data-palette-open]").setAttribute("aria-expanded", "false");
        if (paletteReturnFocus && mobile) paletteReturnFocus.focus();
    };

    const openSubmitDialog = () => {
        const answered = answerCount();
        document.querySelector("[data-confirm-answered]").textContent = String(answered);
        document.querySelector("[data-confirm-unanswered]").textContent = String(total - answered);
        submitDialog.showModal();
    };

    const showSuccessDialog = (destinationUrl) => {
        if (!submitSuccessDialog) return;
        const message = submitSuccessDialog.querySelector("[data-submit-success-message]");
        if (message) {
            message.textContent = "Your exam has been submitted successfully. Redirecting to your result dashboard…";
        }
        submitSuccessDialog.showModal();
        window.setTimeout(() => {
            if (destinationUrl) window.location.assign(destinationUrl);
        }, 1400);
    };

    const submitExam = async () => {
        if (submitStarted) return;
        submitStarted = true;
        await saveQueue;
        submitDialog.close();
        const formData = new FormData(submitForm);
        const payload = new URLSearchParams();
        formData.forEach((value, key) => {
            payload.append(key, String(value));
        });

        try {
            const response = await fetch(submitForm.action, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "text/html,application/xhtml+xml",
                    "X-CSRF-Token": csrfMeta.content
                },
                body: payload.toString()
            });
            updateCsrf(response);
            if (!response.ok) {
                throw new Error(response.status === 419
                    ? "Your session expired. Refresh the page, then submit again. Your saved answers are safe."
                    : "Submission did not go through. Check your connection and tap Submit again. Your saved answers are safe.");
            }
            const destinationUrl = response.url || submitForm.action;
            showSuccessDialog(destinationUrl);
        } catch (error) {
            submitStarted = false;
            submitDialog.showModal();
            saveError.classList.remove("hidden");
            saveError.textContent = error instanceof Error ? error.message : "Submission failed. Please try again.";
        }
    };

    const formatTime = (seconds) => `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`;
    const timer = document.querySelector("[data-timer]");
    const updateTimer = () => {
        const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        timer.textContent = formatTime(remaining);
        timer.classList.remove("bg-amber-50", "text-amber-800", "bg-red-50", "text-red-700", "animate-pulse");
        if (remaining <= 60) timer.classList.add("bg-red-50", "text-red-700", "animate-pulse");
        else if (remaining <= 300) timer.classList.add("bg-amber-50", "text-amber-800");
        timer.dataset.state = remaining <= 60 ? "danger" : remaining <= 300 ? "warning" : "normal";
        if (remaining === 0) {
            window.clearInterval(timerInterval);
            setSaveState("Time is up. Submitting…");
            submitExam();
        }
    };
    const timerInterval = window.setInterval(updateTimer, 1000);
    updateTimer();

    document.querySelectorAll("[data-previous]").forEach((button) => button.addEventListener("click", () => {
        if (currentIndex > 0) { currentIndex--; renderQuestion(); }
    }));
    document.querySelectorAll("[data-next]").forEach((button) => button.addEventListener("click", () => {
        if (currentIndex < total - 1) { currentIndex++; renderQuestion(); }
        else openSubmitDialog();
    }));
    document.querySelector("[data-flag]").addEventListener("click", toggleFlag);
    document.querySelector("[data-open-submit]").addEventListener("click", openSubmitDialog);
    document.querySelector("[data-cancel-submit]").addEventListener("click", () => submitDialog.close());
    document.querySelector("[data-confirm-submit]").addEventListener("click", submitExam);
    document.querySelector("[data-palette-open]").addEventListener("click", (event) => openPalette(event.currentTarget));
    document.querySelector("[data-palette-close]").addEventListener("click", closePalette);
    backdrop.addEventListener("click", closePalette);
    submitDialog.addEventListener("click", (event) => {
        if (event.target === submitDialog) submitDialog.close();
    });

    document.addEventListener("keydown", (event) => {
        if (event.altKey || event.ctrlKey || event.metaKey || submitDialog.open) return;
        const active = document.activeElement;
        const answerRadio = active instanceof HTMLInputElement && active.name.startsWith("question-");
        if (active && ["INPUT", "TEXTAREA", "SELECT"].includes(active.tagName) && !answerRadio) return;
        if (answerRadio && ["ArrowRight", "ArrowDown", "ArrowLeft", "ArrowUp"].includes(event.key)) {
            const optionIndex = options.indexOf(active.value);
            const direction = ["ArrowRight", "ArrowDown"].includes(event.key) ? 1 : -1;
            const targetIndex = optionIndex + direction;
            if (targetIndex >= 0 && targetIndex < options.length) {
                event.preventDefault();
                const target = document.querySelector(`[data-options] input[value="${options[targetIndex]}"]`);
                target.checked = true;
                target.focus();
                target.dispatchEvent(new Event("change", { bubbles: true }));
            }
            return;
        }
        const number = Number(event.key);
        if (number >= 1 && number <= 4) {
            const radio = document.querySelector(`[data-options] input[value="${options[number - 1]}"]`);
            if (radio) { radio.checked = true; radio.dispatchEvent(new Event("change", { bubbles: true })); radio.focus(); }
        } else if (event.key === "Escape" && !backdrop.hidden) {
            event.preventDefault(); closePalette();
        } else if (event.key === "ArrowRight" && currentIndex < total - 1) {
            event.preventDefault(); currentIndex++; renderQuestion();
        } else if (event.key === "ArrowLeft" && currentIndex > 0) {
            event.preventDefault(); currentIndex--; renderQuestion();
        }
    });

    history.pushState({ practiceExam: true }, "", window.location.href);
    window.addEventListener("popstate", () => {
        if (!window.confirm("Leaving will not submit your exam. Do you want to leave?")) {
            history.pushState({ practiceExam: true }, "", window.location.href);
        }
    });
    window.addEventListener("beforeunload", (event) => {
        if (!submitStarted && savePending) { event.preventDefault(); event.returnValue = ""; }
    });

    renderQuestion();
})();