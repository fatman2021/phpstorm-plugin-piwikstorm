package com.piwik.intellijplugins.piwikstorm.inspections;

import com.intellij.codeInspection.ProblemHighlightType;
import com.intellij.codeInspection.ProblemsHolder;
import com.intellij.openapi.components.ServiceManager;
import com.intellij.openapi.project.Project;
import com.intellij.openapi.project.ProjectManager;
import com.intellij.psi.PsiElement;
import com.intellij.psi.PsiElementVisitor;
import com.intellij.psi.PsiReference;
import com.jetbrains.php.lang.inspections.PhpInspection;
import com.jetbrains.php.lang.psi.elements.*;
import com.jetbrains.php.lang.psi.visitors.PhpElementVisitor;
import com.piwik.intellijplugins.piwikstorm.services.PiwikPsiElementMetadataProvider;
import org.jetbrains.annotations.NotNull;

/**
 * Inspection that makes sure plugins do not use non-API methods/classes/fields/etc.
 * of Piwik core or other plugins.
 */
public class PiwikNonApiInspection extends PhpInspection {

    private PiwikPsiElementMetadataProvider metadataProvider = null;

    public PiwikNonApiInspection() {
        // empty
    }

    @NotNull
    @Override
    public PsiElementVisitor buildVisitor(@NotNull final ProblemsHolder holder, boolean isOnTheFly) {
        return new PhpElementVisitor() {
            @Override
            public void visitPhpMethodReference(MethodReference reference) {
                PiwikNonApiInspection.this.check(reference, "Method #ref is not marked as @api", holder);
            }

            @Override
            public void visitPhpFunctionCall(FunctionReference reference) {
                PiwikNonApiInspection.this.check(reference, "Function #ref is not marked as @api", holder);
            }

            @Override
            public void visitPhpClassReference(ClassReference reference) {
                PiwikNonApiInspection.this.check(reference, "Class #ref is not marked as @api", holder);
            }

            @Override
            public void visitPhpClassConstantReference(ClassConstantReference reference) {
                PiwikNonApiInspection.this.check(reference, "Constant #ref is not marked as @api", holder);
            }

            @Override
            public void visitPhpFieldReference(FieldReference reference) {
                PiwikNonApiInspection.this.check(reference, "Field #ref is not marked as @api", holder);
            }

            @Override
            public void visitPhpConstantReference(ConstantReference reference) {
                PiwikNonApiInspection.this.check(reference, "constant #ref is not marked as @api", holder);
            }
        };
    }

    private void check(PsiReference reference, String desc, ProblemsHolder holder) {
        PsiElement referenceElement = reference.resolve();
        if (referenceElement instanceof PhpNamedElement) {
            PhpNamedElement namedElement = (PhpNamedElement) referenceElement;

            if (!this.shouldRunApiCheck(namedElement, reference)) {
                return;
            }

            if (!this.getMetadataProvider().isMarkedWithApi(namedElement)) {
                holder.registerProblem(reference, desc, ProblemHighlightType.LIKE_DEPRECATED);
            }
        }
    }

    private boolean shouldRunApiCheck(PhpNamedElement referencedElement, PsiReference reference) {
        // run the check if:
        //   - the referenced element is in the Piwik\ namespace
        //   - the reference is in a class whose namespace is a Piwik plugin namespace
        //   - the referenced element is not in the same plugin namespace as the class that contains the
        //     code reference
        //      * NOTE: a Piwik plugin namespace is the root namespace for a Piwik plugin (ie,
        //        "Piwik\Plugins\MyPlugin")

        String pluginNamespaceOfReference = this.getMetadataProvider().getPluginNamespaceOfElement(reference.getElement());
        String referencedElementNamespace = referencedElement.getNamespaceName();

        return referencedElementNamespace.startsWith("\\Piwik\\")
            && pluginNamespaceOfReference != null
            && !referencedElementNamespace.startsWith(pluginNamespaceOfReference);
    }

    private PiwikPsiElementMetadataProvider getMetadataProvider() {
        if (this.metadataProvider == null) {
            this.metadataProvider = ServiceManager.getService(PiwikPsiElementMetadataProvider.class);
        }
        return this.metadataProvider;
    }
}
