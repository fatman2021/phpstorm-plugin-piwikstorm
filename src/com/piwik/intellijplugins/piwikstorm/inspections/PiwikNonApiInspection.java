package com.piwik.intellijplugins.piwikstorm.inspections;

import com.intellij.codeInspection.ProblemHighlightType;
import com.intellij.codeInspection.ProblemsHolder;
import com.intellij.openapi.components.ServiceManager;
import com.intellij.psi.PsiElement;
import com.intellij.psi.PsiElementVisitor;
import com.intellij.psi.PsiReference;
import com.jetbrains.php.lang.inspections.PhpInspection;
import com.jetbrains.php.lang.psi.elements.*;
import com.jetbrains.php.lang.psi.visitors.PhpElementVisitor;
import com.piwik.intellijplugins.piwikstorm.services.PiwikPsiElementMetadataProvider;
import org.jetbrains.annotations.NotNull;

import java.util.ArrayList;
import java.util.List;

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
                PiwikNonApiInspection.this.check(reference, "Method '#ref' is not marked as @api", holder);
            }

            @Override
            public void visitPhpFunctionCall(FunctionReference reference) {
                PiwikNonApiInspection.this.check(reference, "Function '#ref' is not marked as @api", holder);
            }

            @Override
            public void visitPhpClassReference(ClassReference reference) {
                PiwikNonApiInspection.this.check(reference, "Class '#ref' is not marked as @api", holder);
            }

            @Override
            public void visitPhpClassConstantReference(ClassConstantReference reference) {
                PiwikNonApiInspection.this.check(reference, "Constant '#ref' is not marked as @api", holder);
            }

            @Override
            public void visitPhpFieldReference(FieldReference reference) {
                PiwikNonApiInspection.this.check(reference, "Field '#ref' is not marked as @api", holder);
            }

            @Override
            public void visitPhpConstantReference(ConstantReference reference) {
                PiwikNonApiInspection.this.check(reference, "constant '#ref' is not marked as @api", holder);
            }

            @Override
            public void visitPhpMethod(Method method) {
                Method superMethod = (Method)PiwikNonApiInspection.this.getSuperMember(method);
                if (superMethod != null) {
                    PiwikNonApiInspection.this.checkOverride(method, superMethod, holder);
                }
            }

            @Override
            public void visitPhpField(Field field) {
                Field superField = (Field)PiwikNonApiInspection.this.getSuperMember(field);
                if (superField != null) {
                    PiwikNonApiInspection.this.checkOverride(field, superField, holder);
                }
            }
        };
    }

    private void checkOverride(PhpClassMember member, PhpClassMember superMember, ProblemsHolder holder) {
        // an overridden method/field is valid if the super member (which is guaranteed to be in core or another plugin
        // because of getSuperMember) is accessible via @api
        if (!this.getMetadataProvider().isMarkedWithApi(superMember)) {
            String message = "Class member '" + member.getName() + "' overrides member that is not marked with @api";
            holder.registerProblem(member, message, ProblemHighlightType.LIKE_DEPRECATED);
        }
    }

    private void check(PsiReference reference, String desc, ProblemsHolder holder) {
        PsiElement referenceElement = reference.resolve();
        if (referenceElement instanceof PhpNamedElement) {
            PhpNamedElement namedElement = (PhpNamedElement) referenceElement;

            if (!this.shouldRunApiCheck(namedElement, reference.getElement())) {
                return;
            }

            if (!this.getMetadataProvider().isMarkedWithApi(namedElement)) {
                holder.registerProblem(reference, desc, ProblemHighlightType.LIKE_DEPRECATED);
            }
        }
    }

    private boolean shouldRunApiCheck(PhpNamedElement referencedElement, PsiElement referenceElement) {
        // run the check if:
        //   - the referenced element is in the Piwik\ namespace
        //   - the reference is in a class whose namespace is a Piwik plugin namespace
        //   - the referenced element is not in the same plugin namespace as the class that contains the
        //     code reference
        //      * NOTE: a Piwik plugin namespace is the root namespace for a Piwik plugin (ie,
        //        "Piwik\Plugins\MyPlugin")

        String pluginNamespaceOfReference = this.getMetadataProvider().getPluginNamespaceOfElement(referenceElement);
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

    private PhpClassMember getSuperMember(PhpClassMember member) {
        List<PhpClass> allBasesAndImplemented = getAllBasesAndInterfaces(member.getContainingClass());

        for (PhpClass baseOrInteface : allBasesAndImplemented) {
            PhpClassMember superMember = null;
            if (member instanceof Field) {
                // NOTE: I have no idea what the second param is for, but if it's 'true', this method returns null.
                superMember = baseOrInteface.findOwnFieldByName(member.getName(), false);
            } else if (member instanceof Method) {
                superMember = baseOrInteface.findOwnMethodByName(member.getName());
            }

            if (superMember == null) {
                continue;
            }

            // don't return super members that are part of the plugin being checked (which can happen if
            // a method in core is overridden more than once in an inheritance chain)
            if (shouldRunApiCheck(superMember, member)) {
                return superMember;
            }
        }

        return null;
    }

    private List<PhpClass> getAllBasesAndInterfaces(PhpClass klass) { // TODO: should use cache either for this method or getSuperMember
        return getAllBasesAndInterfaces(klass, new ArrayList<PhpClass>());
    }

    private List<PhpClass> getAllBasesAndInterfaces(PhpClass klass, List<PhpClass> list) {
        for (PhpClass superClass : klass.getSupers()) {
            list.add(superClass);

            getAllBasesAndInterfaces(superClass, list);
        }

        for (PhpClass implementedInterface : klass.getImplementedInterfaces()) {
            list.add(implementedInterface);

            getAllBasesAndInterfaces(implementedInterface, list);
        }

        return list;
    }
}
