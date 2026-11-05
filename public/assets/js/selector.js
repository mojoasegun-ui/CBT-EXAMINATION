"use strict";

(() => {
    const form = document.querySelector("[data-selector-form]");
    if (!form) return;

    const apiUrl = form.dataset.apiUrl;
    const typeButtons = [...form.querySelectorAll("[data-exam-type]")];
    const subjectGrid = form.querySelector("[data-subjects]");
    const subjectSearch = form.querySelector("[data-subject-search]");
    const subjectCount = form.querySelector("[data-subject-count]");
    const subjectBrowse = form.querySelector("[data-subject-browse]");
    const yearStep = form.querySelector("[data-year-step]");
    const selectorGrid = form.querySelector("[data-selector-grid]");
    const examChoice = form.querySelector("#exam-choice");
    const examIdInput = form.querySelector("#exam-id");
    const startButton = form.querySelector("[data-start-button]");
    const meta = form.querySelector("[data-exam-meta]");
    const description = form.querySelector("[data-exam-description]");
    const typeStatus = form.querySelector("[data-type-status]");
    const status = form.querySelector("[data-selector-status]");
    const selectionCount = document.querySelector("[data-selection-count]");
    const selectionList = document.querySelector("[data-selection-list]");
    const studentNameHiddenInput = form.querySelector("#student-name-hidden");
    const studentEmailHiddenInput = form.querySelector("#student-email-hidden");
    const identityModal = document.querySelector("[data-identity-modal]");
    const openIdentityButtons = document.querySelectorAll("[data-open-identity-modal]");
    const identityName = document.getElementById("identity-name");
    const identityEmail = document.getElementById("identity-email");
    const modalSubmit = document.querySelector("[data-submit-identity]");
    const modalCloseButtons = document.querySelectorAll("[data-close-identity-modal]");
    let activeType = "";
    let activeSubject = "";
    let requestId = 0;
    let loadedSubjects = [];
    let showAllSubjects = false;
    let selectedExamIds = new Map();

    const iconMarkup = {
        languages: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h10M9 3v2c0 6-3 10-6 12M5 10c1 3 3 5 6 6M13 21l4-11 4 11m-6.5-4h5"/></svg>',
        calculator: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8v3H8zM8 14h2m4 0h2m-8 3h2m4 0h2"/></svg>',
        leaf: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4c-9 0-15 3-15 10a6 6 0 0 0 6 6c7 0 9-7 9-16Z"/><path d="M4 21c3-5 7-8 12-11"/></svg>',
        flask: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6m-5 0v7l-6 10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1l-6-10V3M7 16h10"/></svg>',
        atom: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="2"/><ellipse cx="12" cy="12" rx="9" ry="4"/><ellipse cx="12" cy="12" rx="9" ry="4" transform="rotate(60 12 12)"/></svg>',
        landmark: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-6 9 6M5 10h14M6 20v-8m4 8v-8m4 8v-8m4 8v-8M3 21h18"/></svg>',
        chart: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V4m0 16h17M8 16l4-5 3 2 5-7"/></svg>',
        book: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22V5.5ZM4 17.5A2.5 2.5 0 0 1 6.5 15H20M8 7h8m-8 3.5h7"/></svg>',
        sparkles: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3ZM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/></svg>',
        globe: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>',
        sprout: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21v-9m0 4c-4 0-7-2.5-7-7 4.5 0 7 2.5 7 7Zm0-3c0-4.5 2.5-7 7-7 0 4.5-3 7-7 7Z"/></svg>',
        paw: '<svg viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4"/><circle cx="5.5" cy="10" r="1.7"/><circle cx="10" cy="6.5" r="1.7"/><circle cx="15" cy="6.5" r="1.7"/><circle cx="18.5" cy="10" r="1.7"/></svg>',
        bolt: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7Z"/></svg>',
        building: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5l8-3 8 3v16M2 21h20M8 8h1m6 0h1M8 12h1m6 0h1M10 21v-4h4v4"/></svg>',
        briefcase: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V4h8v3M3 12h18m-11 0v2h4v-2"/></svg>',
        monitor: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4M7 8h10m-10 4h6"/></svg>',
        database: '<svg viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>',
        coins: '<svg viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v9c0 1.7 2.7 3 6 3m6-12v3m-12 1c0 1.7 2.7 3 6 3m3 1.5c0-1.4 1.8-2.5 4-2.5s4 1.1 4 2.5-1.8 2.5-4 2.5-4-1.1-4-2.5Zm4-2.5v-2m0 10v-2"/></svg>',
        utensils: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v7m-3-7v4a3 3 0 0 0 6 0V3m-3 7v11m9-18v18m0-18c3 2 4 5 4 9h-4"/></svg>',
        sigma: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4H6l7 8-7 8h13"/></svg>',
        heart: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.5c0 5-8.5 11-8.5 11s-8.5-6-8.5-11a4.5 4.5 0 0 1 8.5-2 4.5 4.5 0 0 1 8.5 2Z"/><path d="M3.5 12h5l2-3 3 6 2-3h5"/></svg>',
        moon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 15A8.5 8.5 0 0 1 9 3.5 8.5 8.5 0 1 0 20.5 15Z"/><path d="m16.5 4 .5 1.5 1.5.5-1.5.5-.5 1.5-.5-1.5-1.5-.5 1.5-.5.5-1.5Z"/></svg>',
        megaphone: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 15-6v14L3 13v-2Zm0 0v2m5.5 1.5L10 21h4l-2-5m6-7a4 4 0 0 1 0 6"/></svg>',
        clipboard: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4V2h6v2M8 9h8m-8 4h8m-8 4h5"/></svg>',
        ruler: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 14-14 4 4L7 21l-4-4Zm11-10 3 3m-6 0 2 2m-6 0 3 3"/></svg>',
        palette: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 0 18h1.5a2 2 0 0 0 1.3-3.5 1.7 1.7 0 0 1 1.1-3h1.6A3.5 3.5 0 0 0 21 11c0-4.4-4-8-9-8Z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7.5" r="1"/><circle cx="15" cy="8" r="1"/></svg>',
        hammer: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 7 3-3 4 4-3 3M13 8l3 3M4 20l9-9m-7 7 2 2"/></svg>',
        default: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>'
    };

    const setStatus = (message) => {
        if (status) status.textContent = message;
    };

    const setActiveToggleState = (buttons, activeButton) => {
        buttons.forEach((button) => {
            const selected = button === activeButton;
            button.setAttribute("aria-pressed", String(selected));
            button.classList.toggle("border-primary", selected);
            button.classList.toggle("bg-blue-50", selected);
            button.classList.toggle("text-primary", selected);
            button.classList.toggle("shadow-sm", selected);
            button.classList.toggle("ring-1", selected);
            button.classList.toggle("ring-primary/20", selected);
        });
    };

    const hasSelection = () => selectedExamIds.size > 0 || Boolean(examIdInput.value);

    const syncSelectedExamInputs = () => {
        const selectedIds = [...selectedExamIds.keys()];
        form.querySelectorAll("input[name='exam_ids[]']").forEach((input) => input.remove());
        selectedIds.forEach((id) => {
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = "exam_ids[]";
            hiddenInput.value = String(id);
            form.append(hiddenInput);
        });
        examIdInput.value = selectedIds[0] || examIdInput.value || "";
        startButton.disabled = !hasSelection();
        if (selectionCount) {
            selectionCount.textContent = selectedIds.length === 0 ? "0 exam selected" : `${selectedIds.length} exam${selectedIds.length > 1 ? "s" : ""} selected`;
        }
    };

    const renderSelectionSummary = () => {
        if (!selectionList) return;
        selectionList.replaceChildren();
        if (selectedExamIds.size === 0) {
            return;
        }

        selectedExamIds.forEach((exam, id) => {
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary shadow-sm transition hover:bg-primary/10";
            chip.innerHTML = `<span>${exam.label}</span><span aria-hidden="true">×</span>`;
            chip.addEventListener("click", () => {
                selectedExamIds.delete(String(id));
                renderSelectionSummary();
                syncSelectedExamInputs();
                setStatus("Exam removed from your selection.");
            });
            selectionList.append(chip);
        });
    };

    const resetExamChoice = (message = "Choose a subject first") => {
        examChoice.replaceChildren(new Option(message, ""));
        examChoice.disabled = true;
        examIdInput.value = "";
        startButton.disabled = !hasSelection();
        meta.textContent = "Choose a set to see its format.";
        description.textContent = "Available practice sets show their question count and time limit here.";
    };

    const makeSubjectButton = (subject) => {
        const button = document.createElement("button");
        button.type = "button";
        button.dataset.subjectSlug = subject.slug;
        button.setAttribute("aria-pressed", "false");
        button.className = "subject-option min-h-24 rounded-xl border border-slate-200 bg-white p-3 text-left transition duration-150 hover:-translate-y-0.5 hover:border-primary hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600";

        const icon = document.createElement("span");
        icon.className = "subject-option-icon grid h-9 w-9 place-items-center rounded-lg bg-blue-50 text-primary";
        icon.innerHTML = iconMarkup[subject.icon] || iconMarkup.default;
        const svg = icon.querySelector("svg");
        svg.classList.add("h-5", "w-5");
        svg.setAttribute("fill", "none");
        svg.setAttribute("stroke", "currentColor");
        svg.setAttribute("stroke-width", "1.7");
        svg.setAttribute("stroke-linecap", "round");
        svg.setAttribute("stroke-linejoin", "round");
        const label = document.createElement("span");
        label.className = "mt-2 block text-sm font-bold leading-tight text-ink";
        label.textContent = subject.name;
        const availability = document.createElement("span");
        availability.className = "mt-1 block text-[11px] font-semibold text-muted";
        const setCount = Number(subject.available_exam_count || 0);
        availability.textContent = setCount ? `${setCount} practice set${setCount === 1 ? "" : "s"}` : "Coming soon";
        button.append(icon, label, availability);
        button.addEventListener("click", () => {
            activeSubject = subject.slug;
            if (typeStatus) {
                const typeLabel = typeButtons.find((item) => item.dataset.examType === activeType)?.textContent?.trim() || "Selected exam type";
                typeStatus.textContent = `${typeLabel} · ${subject.name} selected`;
            }
            subjectGrid.querySelectorAll("[data-subject-slug]").forEach((item) => {
                const selected = item === button;
                item.setAttribute("aria-pressed", String(selected));
                item.classList.toggle("border-primary", selected);
                item.classList.toggle("bg-blue-50", selected);
                item.classList.toggle("text-primary", selected);
                item.classList.toggle("shadow-sm", selected);
            });
            loadExams();
        });
        return button;
    };

    const renderSubjects = () => {
        const searchTerm = subjectSearch.value.trim().toLocaleLowerCase();
        const availableSubjects = loadedSubjects.filter((subject) => Number(subject.available_exam_count || 0) > 0);
        const subjectsToShow = showAllSubjects || searchTerm ? loadedSubjects : availableSubjects;
        const visibleSubjects = subjectsToShow.filter((subject) => subject.name.toLocaleLowerCase().includes(searchTerm));
        subjectBrowse.hidden = loadedSubjects.length <= availableSubjects.length || searchTerm !== "";
        subjectBrowse.textContent = showAllSubjects ? "Show available subjects only" : `Browse all ${loadedSubjects.length} subjects`;
        subjectGrid.replaceChildren();
        if (!visibleSubjects.length) {
            const empty = document.createElement("p");
            empty.className = "col-span-full rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-muted";
            empty.textContent = searchTerm ? "No subjects match that search." : "No practice subjects are available yet.";
            subjectGrid.append(empty);
            return;
        }
        visibleSubjects.forEach((subject) => subjectGrid.append(makeSubjectButton(subject)));
    };

    const loadSubjects = async () => {
        const currentRequest = ++requestId;
        activeSubject = "";
        loadedSubjects = [];
        showAllSubjects = false;
        if (typeStatus) {
            typeStatus.textContent = activeType ? `${typeButtons.find((item) => item.dataset.examType === activeType)?.textContent?.trim() || "Exam type"} selected` : "Select an exam type to see subjects.";
        }
        subjectSearch.value = "";
        subjectSearch.disabled = true;
        yearStep.classList.add("hidden");
        selectorGrid.classList.remove("lg:grid-cols-3");
        selectorGrid.classList.add("lg:grid-cols-2");
        subjectBrowse.hidden = true;
        subjectCount.textContent = "Loading subject catalog…";
        subjectGrid.replaceChildren();
        const loading = document.createElement("p");
        loading.className = "col-span-full rounded-xl bg-slate-50 px-4 py-5 text-sm text-muted";
        loading.textContent = "Loading subjects…";
        subjectGrid.append(loading);
        resetExamChoice();
        setStatus("Loading subjects.");
        try {
            const url = new URL(apiUrl, window.location.href);
            url.searchParams.set("exam_type", activeType);
            const response = await fetch(url, { headers: { Accept: "application/json" }, credentials: "same-origin" });
            const payload = await response.json();
            if (currentRequest !== requestId) return;
            if (!response.ok) throw new Error(payload.error || "Subjects could not be loaded.");
            subjectGrid.replaceChildren();
            if (!payload.subjects?.length) {
                loading.textContent = "No practice subjects are available for this exam type yet.";
                subjectGrid.append(loading);
                subjectCount.textContent = "No subjects available.";
                setStatus("No subjects are currently available.");
                return;
            }
            loadedSubjects = payload.subjects.sort((left, right) => Number(right.available_exam_count || 0) - Number(left.available_exam_count || 0) || left.name.localeCompare(right.name));
            subjectSearch.disabled = false;
            const availableCount = loadedSubjects.filter((subject) => Number(subject.available_exam_count || 0) > 0).length;
            const moreCount = loadedSubjects.length - availableCount;
            subjectCount.textContent = `${availableCount} ready for practice${moreCount ? ` · ${moreCount} more in the catalog` : ""}`;
            renderSubjects();
            setStatus(`${loadedSubjects.length} subjects loaded.`);
        } catch (error) {
            if (currentRequest !== requestId) return;
            subjectGrid.replaceChildren();
            const message = document.createElement("p");
            message.className = "col-span-full rounded-xl border border-red-200 bg-red-50 px-4 py-5 text-sm text-red-800";
            message.textContent = error.message || "Subjects could not be loaded. Please try again.";
            subjectGrid.append(message);
            subjectCount.textContent = "Subject catalog unavailable.";
            setStatus("Subjects could not be loaded.");
        }
    };

    const loadExams = async () => {
        const currentRequest = ++requestId;
        yearStep.classList.remove("hidden");
        selectorGrid.classList.remove("lg:grid-cols-2");
        selectorGrid.classList.add("lg:grid-cols-3");
        resetExamChoice("Loading available years…");
        examChoice.disabled = true;
        setStatus("Loading practice years.");
        try {
            const url = new URL(apiUrl, window.location.href);
            url.searchParams.set("exam_type", activeType);
            url.searchParams.set("subject", activeSubject);
            const response = await fetch(url, { headers: { Accept: "application/json" }, credentials: "same-origin" });
            const payload = await response.json();
            if (currentRequest !== requestId) return;
            if (!response.ok) throw new Error(payload.error || "Practice exams could not be loaded.");
            examChoice.replaceChildren(new Option(payload.exams.length ? "Choose a year" : "No practice set available", ""));
            payload.exams.forEach((exam) => {
                const option = new Option(`${exam.year} practice series · ${exam.total_questions} questions`, String(exam.id));
                option.dataset.questions = String(exam.total_questions);
                option.dataset.duration = String(exam.duration_minutes);
                option.dataset.title = exam.title;
                examChoice.append(option);
            });
            examChoice.disabled = payload.exams.length === 0;
            if (payload.exams.length === 0) {
                meta.textContent = "Practice set coming soon.";
                description.textContent = "This subject is in the catalog; its question bank is still being prepared.";
            }
            setStatus(payload.exams.length ? `${payload.exams.length} practice sets loaded.` : "No practice sets are available for this subject yet.");
        } catch (error) {
            if (currentRequest !== requestId) return;
            examChoice.replaceChildren(new Option(error.message || "Could not load years", ""));
            examChoice.disabled = true;
            setStatus("Practice years could not be loaded.");
        }
    };

    typeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            activeType = button.dataset.examType;
            if (typeStatus) {
                typeStatus.textContent = `${button.textContent.trim()} selected`;
            }
            setActiveToggleState(typeButtons, button);
            loadSubjects();
        });
    });

    subjectSearch.addEventListener("input", renderSubjects);
    subjectBrowse.addEventListener("click", () => {
        showAllSubjects = !showAllSubjects;
        renderSubjects();
    });

    const openIdentityModal = () => {
        if (!identityModal) return;
        syncStudentProfile();
        identityModal.classList.remove("hidden");
        identityModal.classList.add("flex");
        identityModal.setAttribute("aria-hidden", "false");
        window.setTimeout(() => {
            identityName?.focus();
        }, 60);
    };

    const closeIdentityModal = () => {
        if (!identityModal) return;
        identityModal.classList.add("hidden");
        identityModal.classList.remove("flex");
        identityModal.setAttribute("aria-hidden", "true");
    };

    identityModal?.addEventListener("click", (event) => {
        if (event.target === identityModal) {
            closeIdentityModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && identityModal && !identityModal.classList.contains("hidden")) {
            closeIdentityModal();
        }
    });

    const syncStudentProfile = () => {
        const name = (identityName?.value ?? studentNameHiddenInput?.value ?? "").trim();
        const email = (identityEmail?.value ?? studentEmailHiddenInput?.value ?? "").trim();
        if (studentNameHiddenInput) {
            studentNameHiddenInput.value = name;
        }
        if (studentEmailHiddenInput) {
            studentEmailHiddenInput.value = email;
        }
        if (identityName) {
            identityName.value = name;
        }
        if (identityEmail) {
            identityEmail.value = email;
        }
    };

    const validateIdentity = () => {
        syncStudentProfile();
        const name = (studentNameHiddenInput?.value ?? identityName?.value ?? "").trim();
        const email = (studentEmailHiddenInput?.value ?? identityEmail?.value ?? "").trim();
        if (name.length < 2) {
            setStatus("Please enter your first name before continuing.");
            identityName?.focus();
            return false;
        }
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            setStatus("Please enter a valid email address before continuing.");
            identityEmail?.focus();
            return false;
        }
        return true;
    };

    examChoice.addEventListener("change", () => {
        const selected = examChoice.selectedOptions[0];
        const examId = selected && selected.value ? String(selected.value) : "";
        if (!examId) {
            return;
        }

        selectedExamIds.set(examId, {
            label: selected.textContent.trim(),
            title: selected.dataset.title || selected.textContent.trim(),
        });
        renderSelectionSummary();
        syncSelectedExamInputs();
        meta.textContent = `${selected.dataset.questions} questions · ${selected.dataset.duration} minutes · Instant result`;
        description.textContent = selected.dataset.title;
        setStatus(`${selected.dataset.title} added to your selection. Continue when ready.`);
        examChoice.value = examId;
    });

    // The short animation that plays before an exam opens, then the form is submitted.
    const launchLayer = document.querySelector("[data-launch]");
    let launching = false;
    const launchExam = () => {
        if (launching) return;
        launching = true;
        if (!launchLayer || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            form.requestSubmit();
            return;
        }
        const first = (studentNameHiddenInput?.value || "").trim().split(/\s+/)[0];
        const hello = launchLayer.querySelector("[data-launch-hello]");
        if (hello) hello.textContent = first ? `Good luck, ${first}!` : "Good luck!";
        launchLayer.hidden = false;
        launchLayer.classList.add("is-on");
        document.body.classList.add("menu-open");
        const steps = [...launchLayer.querySelectorAll("[data-step]")];
        steps.forEach((li, i) => window.setTimeout(() => li.classList.add("is-done"), 450 + i * 550));
        window.setTimeout(() => form.requestSubmit(), 450 + steps.length * 550 + 150);
    };
    window.addEventListener("pageshow", (event) => {
        if (event.persisted && launchLayer) {
            launching = false;
            launchLayer.hidden = true;
            launchLayer.classList.remove("is-on");
            launchLayer.querySelectorAll("[data-step]").forEach((li) => li.classList.remove("is-done"));
            document.body.classList.remove("menu-open");
        }
    });
    const profileKnown = () => {
        syncStudentProfile();
        const name = (studentNameHiddenInput?.value || "").trim();
        const email = (studentEmailHiddenInput?.value || "").trim();
        return name.length >= 2 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    openIdentityButtons.forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            if (!hasSelection()) {
                setStatus("Choose a practice set before continuing.");
                return;
            }
            if (profileKnown()) {
                launchExam(); // details already known: no pop-up
                return;
            }
            syncStudentProfile();
            openIdentityModal();
        });
    });

    // Header avatar and drawer card: always open the profile card, even before an exam is chosen.
    const PROFILE_KEY = "examcore-profile";
    const paintAvatar = (name) => {
        document.querySelectorAll("[data-avatar-letter]").forEach((slot) => {
            const letter = (name || "").trim().charAt(0).toUpperCase();
            slot.textContent = letter;
            slot.classList.toggle("hidden", letter === "");
            slot.previousElementSibling?.classList.toggle("hidden", letter !== "");
        });
    };
    try {
        let saved = JSON.parse(localStorage.getItem(PROFILE_KEY) || "null");
        if ((!saved || !saved.name) && form.dataset.knownName) {
            saved = { name: form.dataset.knownName, email: form.dataset.knownEmail || "" };
        }
        if (saved && saved.name) {
            if (identityName) identityName.value = saved.name;
            if (identityEmail) identityEmail.value = saved.email || "";
            syncStudentProfile();
            paintAvatar(saved.name);
        }
    } catch (error) {
        /* private mode: the profile simply is not remembered */
    }
    document.querySelectorAll("[data-open-profile]").forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            document.querySelector("[data-menu-close]")?.click();
            openIdentityModal();
        });
    });

    modalCloseButtons.forEach((button) => {
        button.addEventListener("click", closeIdentityModal);
    });

    modalSubmit?.addEventListener("click", (event) => {
        event.preventDefault();
        const name = (identityName?.value ?? "").trim();
        const email = (identityEmail?.value ?? "").trim();
        if (studentNameHiddenInput) {
            studentNameHiddenInput.value = name;
        }
        if (studentEmailHiddenInput) {
            studentEmailHiddenInput.value = email;
        }
        if (!validateIdentity()) {
            return;
        }
        try {
            localStorage.setItem(PROFILE_KEY, JSON.stringify({ name, email }));
        } catch (error) {
            /* ignore */
        }
        paintAvatar(name);
        if (!hasSelection()) {
            closeIdentityModal();
            setStatus("Profile saved. Choose a practice set to begin.");
            return;
        }
        closeIdentityModal();
        syncStudentProfile();
        launchExam();
    });

    form.addEventListener("submit", (event) => {
        syncStudentProfile();
        if (!hasSelection()) {
            event.preventDefault();
            setStatus("Choose an exam type, subject, and year before starting.");
            return;
        }

        if (!validateIdentity()) {
            event.preventDefault();
        }
    });

    const themeToggle = document.querySelector("[data-theme-toggle]");
    const themeIcon = themeToggle?.querySelector(".theme-toggle__icon");
    const applyTheme = (theme) => {
        const resolvedTheme = theme === "dark" ? "dark" : "light";
        document.body.dataset.theme = resolvedTheme;
        document.documentElement.dataset.theme = resolvedTheme;
        if (themeIcon) {
            themeIcon.textContent = resolvedTheme === "dark" ? "🌙" : "☀️";
        }
        if (themeToggle) {
            themeToggle.setAttribute("aria-label", resolvedTheme === "dark" ? "Switch to light mode" : "Switch to dark mode");
        }
        localStorage.setItem("examcore-theme", resolvedTheme);
    };

    const savedTheme = localStorage.getItem("examcore-theme");
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        applyTheme(window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    }
    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const currentTheme = document.body.dataset.theme === "dark" ? "dark" : "light";
            applyTheme(currentTheme === "dark" ? "light" : "dark");
        });
    }

    const menuToggle = document.querySelector("[data-menu-toggle]");
    const mobileMenu = document.querySelector("[data-mobile-menu]");
    const closeMenuButton = document.querySelector("[data-menu-close]");
    const siteHeader = document.querySelector(".site-header");
    if (siteHeader) {
        const updateHeaderState = () => {
            siteHeader.classList.toggle("is-scrolled", window.scrollY > 12);
        };
        updateHeaderState();
        window.addEventListener("scroll", updateHeaderState, { passive: true });
    }
    if (menuToggle && mobileMenu) {
        const setMenuState = (open) => {
            mobileMenu.classList.toggle("hidden", !open);
            mobileMenu.classList.toggle("is-open", open);
            menuToggle.setAttribute("aria-expanded", String(open));
            menuToggle.setAttribute("aria-label", open ? "Close practice menu" : "Open practice menu");
            document.body.classList.toggle("menu-open", open);
        };
        const closeMenu = (restoreFocus = false) => {
            setMenuState(false);
            if (restoreFocus) menuToggle.focus();
        };

        menuToggle.addEventListener("click", () => {
            const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
            setMenuState(!isOpen);
            if (!isOpen) mobileMenu.querySelector("a")?.focus();
        });
        if (closeMenuButton) closeMenuButton.addEventListener("click", () => closeMenu(true));
        mobileMenu.querySelectorAll("[data-menu-link]").forEach((link) => link.addEventListener("click", () => closeMenu()));
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && menuToggle.getAttribute("aria-expanded") === "true") {
                closeMenu(true);
            }
        });
        document.addEventListener("click", (event) => {
            if (menuToggle.getAttribute("aria-expanded") === "true" && !mobileMenu.contains(event.target) && !menuToggle.contains(event.target)) {
                closeMenu();
            }
        });
    }

    const revealElements = [...document.querySelectorAll("[data-reveal]")];
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (revealElements.length && "IntersectionObserver" in window && !reducedMotion.matches) {
        document.documentElement.classList.add("motion-ready");
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: "0px 0px -24px 0px" });
        revealElements.forEach((element) => observer.observe(element));
        reducedMotion.addEventListener("change", (event) => {
            if (event.matches) {
                document.documentElement.classList.remove("motion-ready");
                revealElements.forEach((element) => element.classList.add("is-visible"));
                observer.disconnect();
            }
        });
    }

    // ---- Lively touches: rotating exam name, count-up numbers, pointer parallax, button ripple ----
    const calm = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const word = document.querySelector("[data-rotate]");
    if (word && !calm) {
        const words = word.dataset.rotate.split("|");
        let at = 0;
        window.setInterval(() => {
            word.classList.add("is-out");
            window.setTimeout(() => {
                at = (at + 1) % words.length;
                word.textContent = words[at];
                word.classList.remove("is-out");
            }, 260);
        }, 2200);
    }
    const counters = [...document.querySelectorAll("[data-count]")];
    if (counters.length && "IntersectionObserver" in window && !calm) {
        const run = (el) => {
            const end = Number(el.dataset.count);
            const suffix = el.dataset.suffix || "";
            const t0 = performance.now();
            const tick = (now) => {
                const k = Math.min(1, (now - t0) / 1400);
                const v = Math.round(end * (1 - Math.pow(1 - k, 3)));
                el.textContent = v.toLocaleString("en-US") + (k === 1 ? suffix : "");
                if (k < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        const seen = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) {
                run(entry.target);
                seen.unobserve(entry.target);
            }
        }), { threshold: 0.6 });
        counters.forEach((el) => seen.observe(el));
    }
    const hero = document.querySelector("#hero-title")?.closest("section");
    const parallax = hero ? [...hero.querySelectorAll("[data-parallax], .hero-chip, .hero-aurora")] : [];
    if (hero && parallax.length && !calm && window.matchMedia("(pointer: fine)").matches) {
        let frame = 0;
        hero.addEventListener("pointermove", (event) => {
            if (frame) return;
            frame = requestAnimationFrame(() => {
                frame = 0;
                const r = hero.getBoundingClientRect();
                const x = (event.clientX - r.left) / r.width - 0.5;
                const y = (event.clientY - r.top) / r.height - 0.5;
                hero.style.setProperty("--px", x.toFixed(3));
                hero.style.setProperty("--py", y.toFixed(3));
            });
        });
    }
    document.addEventListener("pointerdown", (event) => {
        const target = event.target instanceof Element ? event.target.closest(".cta-shine, .btn-ripple, [data-start-button]") : null;
        if (!target || calm) return;
        const r = target.getBoundingClientRect();
        const dot = document.createElement("span");
        dot.className = "ripple-dot";
        dot.style.left = `${event.clientX - r.left}px`;
        dot.style.top = `${event.clientY - r.top}px`;
        target.append(dot);
        window.setTimeout(() => dot.remove(), 650);
    });
})();